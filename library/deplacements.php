<?php
include_once('dblink_objects/deplacement.php');
class deplacements {
	// DEBUT PROLOGUE LIB
	private $class;
	private $instance = array();

	public function	__construct($class)
	{

	}

	public function loadLib($class) 
	{
		if (is_array($class))
		{
			foreach ($class AS $key => $value)
				$this->$key = $value;
		}
	}
	public function __get($key)
	{
		return (isset($this->class[$key])) ? $this->class[$key] : NULL;
	}

	public function __set($key, $val)
	{
		$this->class[$key] = $val;
	}
	// FIN PROLOGUE LIB

	// Permet de sauvegarder les donnees des defenses contenu dans $object en bdd et dans redis
	public function save($object)
	{
		self::saveBDD($object->getClass(), $object->id);
		self::saveRedis($object->getClass(), $object->id);
	}

	public function add($object)
	{
		$this->db->query('INSERT INTO `deplacements` SET 
							`to_user_id` = "'.$object['to_user_id'].'",
							`from_planet_id` = "'.$object['from_planet_id'].'",
							`to_planet_id` = "'.$object['to_planet_id'].'",
							`from_user_id` = "'.$object['from_user_id'].'",
							`start` = "'.time().'",
							`time_go` = "'.$object['time_go'].'",
							`time_action` = "'.$object['time_action'].'",
							`time_back` = "'.$object['time_back'].'",
							`distance` = "'.$object['distance'].'",
							`object` = "'.mysql_real_escape_string(json_encode($object['object'])).'",
							`ressources` = "'.mysql_real_escape_string(json_encode($object['ressources'])).'",
							`type` = "'.$object['type'].'",
							`behavior_type` = "'.$object['behavior_type'].'"');
		$user = $this->users->get($obj->from_user_id);
		if ($object['distance'] > 15)
			$this->success->add($user, "explorateur", 1);
		if ($object['distance'] > 26)
			$this->success->add($user, "explorateur", 2);
		if ($object['distance'] > 42)
			$this->success->add($user, "explorateur", 3);
		$this->users->addStats($user, "nb_move", 1);
		$lastid = $this->db->getLastId();
		// faire une restriction quand meme envers les attaques (depends de techno ou je ne sais pas quoi avant de voir l'attaque de l'ennemie)
		if ($this->redis->keys("user_".$object['to_user_id']))
			$this->redis->hset("deplacements_".$object['to_user_id'], $lastid, json_encode(array('id' => $lastid)));
	}


	public function allInputMove($user_id)
	{
		$res = $this->db->query('SELECT d.*, p.avatar as planet_avatar, p.nom as name_to, p.galaxie, p.id as planet_id, u.login FROM `deplacements` d 
									LEFT JOIN planete p ON p.id = d.to_planet_id
									LEFT JOIN user u ON u.user_id = d.to_user_id
								WHERE d.`to_user_id` = "'.$user_id.'" AND (d.`active` = "waiting" or d.`active` = "action") ');
		if ($res->count > 0)
			foreach ($res->rows AS $key => $v)
				$res->rows[$key]['ressources'] = json_decode($v['ressources'], true);
		return $res->rows;
	}

	public function allOutputMove($user_id)
	{
		$res = $this->db->query('SELECT d.*, p.avatar as planet_avatar, p.nom as name_to, p.galaxie, p.id as planet_id, u.login FROM `deplacements` d 
									LEFT JOIN planete p ON p.id = d.to_planet_id
									LEFT JOIN user u ON u.user_id = d.to_user_id
								WHERE d.`from_user_id` = "'.$user_id.'" AND (d.`active` = "waiting" or (d.`active` = "action" and d.type != "espionnage"))');
		if ($res->count > 0)
			foreach ($res->rows AS $key => $v)
				$res->rows[$key]['ressources'] = json_decode($v['ressources'], true);
		return $res->rows;
	}

	public function inputMove($planet_id, $active)
	{
		$res = $this->db->query('SELECT `id` FROM `deplacements` WHERE `to_planet_id` = "'.$planet_id.'" AND `active` = "'.$active.'"');
		return $res->rows;
	}

	public function outputMove($planet_id, $active)
	{
		$res = $this->db->query('SELECT `id` FROM `deplacements` WHERE `from_planet_id` = "'.$planet_id.'" AND `active` = "'.$active.'"');
		return $res->rows;
	}

	//
	public function checkMoves()
	{
		$array = array();
		$redislist = "";
		$keys = $this->redis->keys("move_*");
		$i = 0;
		foreach ($keys AS $val)
		{
			if ($i > 0)
				$redislist .= ",";
			$id = str_replace("move_", "", $val);
			$array[$id] = $id;
			$redislist .= $id;
			$i++;
		}
		$in = "";
		if (strlen($redislist) > 0)
			$in = 'AND `id` NOT IN('.$redislist.')';
		$query = $this->db->query('SELECT * FROM `deplacements` WHERE `active` = "waiting" '.$in);
		foreach ($query->rows AS $key => $val)
		{
			self::saveRedis($val, $val['id']);
			$array[$val['id']] = $val['id'];
		}
		return $array;
	}

	// on sauvegarde les donnees des defenses contenu dans $vars dans redis
	public function saveRedis($vars, $move_id)
	{
		$vars['object'] = json_encode($vars['object']);
		$vars['ressources'] = json_encode($vars['ressources']);
		$json = json_encode($vars);
		$this->redis->hset("move_".$move_id, "json", $json);
	}
	// on sauvegarde les donnees des defenses contenu dans $vars en bdd
	public function saveBDD($vars, $move_id)
	{
		$this->db->query('UPDATE `deplacements` SET `active` = "'.$vars['active'].'", `ressources` = "'.mysql_real_escape_string(json_encode($vars['ressources'])).'", `object` = "'.mysql_real_escape_string(json_encode($vars['object'])).'" WHERE id = "'.$move_id.'"');
	}

	public function delete($object)
	{
		$object->active = 'done';
		$vaisseau = $this->vaisseaux->get($object->from_planet_id);
		$ressource = $this->ressources->get($object->from_planet_id);
		$this->vaisseaux->add($vaisseau, $object->object);
		$this->ressources->add($ressource, $object->ressources);
		$this->redis->expire("move_".$object->move_id, 0);
		self::saveBDD($object->getClass(), $object->move_id);
	}

	// on recupere les donnees soit dans redis soit en bdd et on genere l'instance de l'objet deplacement avant de le renvoyer
	public function get($move_id)
	{
		$needToSave = false;
		if (isset($this->instance[$move_id]))
			return $this->instance[$move_id];
		$res = $this->redis->hgetall("move_".$move_id);
		if (count($res) > 0)
			$json = $res['json'];
		else
		{
			$needToSave = true;
			$req = $this->db->query('SELECT * FROM `deplacements` WHERE `id` = "'.$move_id.'"');
			if ($req->count == 0)
				return NULL;
			$json = json_encode($req->row);
		}
		$this->instance[$move_id] = new deplacement($json, $move_id);
        if ($needToSave)
        	self::saveRedis($this->instance[$move_id]->getClass(), $move_id);
		return $this->instance[$move_id];
	}
}
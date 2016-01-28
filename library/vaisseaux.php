<?php
include_once('dblink_objects/vaisseau.php');
class vaisseaux {
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
	// Permet de sauvegarder les donnees des vaisseaux contenu dans $object en bdd et dans redis
	public function save($object)
	{
		self::saveBDD($object->getClass(), $object->planet_id);
		self::saveRedis($object->getClass(), $object->planet_id);
	}

	// on sauvegarde les donnees des vaisseaux contenu dans $vars dans redis
	public function saveRedis($vars, $planet_id)
	{
		$json = json_encode($vars);
		$this->redis->hset("vaisseau_".$planet_id, "json", $json);
		$this->redis->expire("vaisseau_".$planet_id, 1800);
	}
	// on sauvegarde les donnees des vaisseaux contenu dans $vars en bdd
	public function saveBDD($vars, $planet_id)
	{
		$this->db->query('UPDATE `batiments` SET vaisseaux = "'.mysql_real_escape_string(json_encode($vars)).'" WHERE planet_id = "'.$planet_id.'"');
	}

	public function reset($planet_id)
	{
		$this->db->query('UPDATE `batiments` SET `vaisseaux` = "[]" WHERE `planet_id` = "'.$planet_id.'"');
	}
	
	public function take($obj, $ships)
	{
		foreach ($ships AS $code => $value)
		{
			$c = $obj->$code;
			if ($c['number'] < $value['number'])
				return false;
		}
		foreach ($ships AS $code => $value)
		{
			$c = $obj->$code;
			if ($c['number'] >= $value['number'])
				$c['number'] -= $value['number'];
			$obj->$code = $c;
		}
		self::save($obj);
		return true;
	}

	public function add($obj, $ships)
	{
		foreach ($ships AS $code => $value)
		{
			$c = $obj->$code;
			if (!isset($c['number']))
				$c['number'] = 0;
			$c['number'] += $value['number'];
			$obj->$code = $c;
		}
		self::save($obj);
		return true;
	}

	public function create($array, $min = false)
	{
		$ext = $this->redis->hgetall("vaisseau");
		if (count($ext) == 0)
		{
			$a = array();
			$query = $this->db->query('SELECT * FROM `batiments_type` WHERE `type` = "vaisseaux"');
			foreach ($query->rows AS $val)
				$a[$val['code']] = $val;
			$ext = $a;
			$this->redis->hset("vaisseau", "json", json_encode($ext));
			$this->redis->expire("vaisseau", DAY);
		}
		else
			$ext = json_decode($ext['json'], true);
		return new vaisseau($array, $ext, -1, $min);
	}
  // on recupere les donnees des vaisseaux et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql	
	public function get($planet_id, $min = false, $nocache = false)
	{
		if ($nocache === true)
		{
			$this->instance = array();
			$this->redis->expire("vaisseau", 0);
		}
		$needToSave = false;
		if (isset($this->instance[$planet_id]))
			return $this->instance[$planet_id];
		$res = $this->redis->hgetall("vaisseau_".$planet_id);
		$ext = $this->redis->hgetall("vaisseau");
		if (count($ext) == 0)
		{
			$array = array();
			$query = $this->db->query('SELECT * FROM `batiments_type` WHERE `type` = "vaisseaux" ORDER BY `attaque_base`, `attaque_nb`, `defense_base` ASC');
			foreach ($query->rows AS $val)
				$array[$val['code']] = $val;
			$ext = $array;
			$this->redis->hset("vaisseau", "json", json_encode($ext));
			$this->redis->expire("vaisseau", DAY);
		}
		else
			$ext = json_decode($ext['json'], true);
		if (count($res) > 0)
			$json = $res['json'];
		else
		{
			$req = $this->db->query('SELECT `vaisseaux` FROM `batiments` WHERE `planet_id` = "'.$planet_id.'"');
			$json = $req->row['vaisseaux'];
			if ($this->users->isLogged() && $_SESSION['user']['planet_id'] == $planet_id)
				$needToSave = true;
		}
		$this->instance[$planet_id] = new vaisseau($json, $ext, $planet_id, $min);
        if ($needToSave)
        	self::saveRedis($this->instance[$planet_id]->getClass(), $planet_id);
		return $this->instance[$planet_id];
	}
	// dans cette methode je sais que l'objet que je recois est de type deplacement et qu'il faut faire l'action requise ..
	public function run($obj)
	{
		
	}

}
?>
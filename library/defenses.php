<?php
include_once('dblink_objects/defense.php');
class defenses {
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
		self::saveBDD($object->getClass(), $object->planet_id);
		self::saveRedis($object->getClass(), $object->planet_id);
	}

	// on sauvegarde les donnees des defenses contenu dans $vars dans redis
	public function saveRedis($vars, $planet_id)
	{
		$json = json_encode($vars);
		$this->redis->hset("defense_".$planet_id, "json", $json);
		$this->redis->expire("defense_".$planet_id, 1800);
	}
	// on sauvegarde les donnees des defenses contenu dans $vars en bdd
	public function saveBDD($vars, $planet_id)
	{
		$this->db->query('UPDATE `batiments` SET defensif = "'.mysql_real_escape_string(json_encode($vars)).'" WHERE planet_id = "'.$planet_id.'"');
	}
	public function reset($planet_id)
	{
		$this->db->query('UPDATE `batiments` SET `defensif` = "[]" WHERE `planet_id` = "'.$planet_id.'"');
	}   // on recupere les donnees des defenses et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql	
	public function get($planet_id, $min = false, $nocache = false)
	{
		if ($nocache === true)
		{
			$this->instance = array();
			$this->redis->expire("defense", 0);
		}
		$needToSave = false;
		if (isset($this->instance[$planet_id]))
			return $this->instance[$planet_id];
		$res = $this->redis->hgetall("defense_".$planet_id);
		$ext = $this->redis->hgetall("defensif");
		if (count($ext) == 0)
		{
			$array = array();
			$query = $this->db->query('SELECT * FROM `batiments_type` WHERE `type` = "defensif" ORDER BY `attaque_base`, `defense_base` ASC');
			foreach ($query->rows AS $val)
				$array[$val['code']] = $val;
			$ext = $array;
			$this->redis->hset("defensif", "json", json_encode($ext));
			$this->redis->expire("defensif", DAY);
		}
		else
			$ext = json_decode($ext['json'], true);
		if (count($res) > 0)
			$json = $res['json'];
		else
		{
			$req = $this->db->query('SELECT `defensif` FROM `batiments` WHERE `planet_id` = "'.$planet_id.'"');
			$json = $req->row['defensif'];
			if ($this->users->isLogged() && $_SESSION['user']['planet_id'] == $planet_id)
				$needToSave = true;
		}
		$this->instance[$planet_id] = new defense($json, $ext, $planet_id, $min);
        if ($needToSave)
        	self::saveRedis($this->instance[$planet_id]->getClass(), $planet_id);
		return $this->instance[$planet_id];
	}

}
?>
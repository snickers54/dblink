<?php
include_once('dblink_objects/alliance.php');
class alliances {
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
	// Permet de sauvegarder les donnees des ressources contenu dans $object en bdd et dans redis
	public function save($object)
	{
		self::saveBDD($object->getClass());
		self::saveRedis($object->getDatas());
	}

	// on sauvegarde les donnees des ressources contenu dans $vars dans redis
	public function saveRedis($vars)
	{
		$alliance_id = $vars['id'];
		$json = json_encode($vars);
		$this->redis->hset("alliance_".$alliance_id, "json", $json);
		$this->redis->expire("alliance_".$alliance_id, 1800);
	}
	// on sauvegarde les donnees des ressources contenu dans $vars en bdd
	public function saveBDD($vars)
	{
		$alliance_id = $vars['id'];
		unset($vars['id']);
		$sql = "";
		$i = 0;
		foreach ($vars AS $key => $val)
		{
			if ($i > 0)
				$sql .= ", ";
			$sql .= "{$key}='{$val}'";
			$i++;
		}
		$this->db->query('UPDATE `alliance` SET '.$sql.' WHERE id = "'.$alliance_id.'"');
	}
  // on recupere les donnees des ressources et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql	
	public function get($alliance_id)
	{
		$needToSave = false;
		if (isset($this->instance[$alliance_id]))
			return $this->instance[$alliance_id];
		$res = $this->redis->hgetall("alliance_".$alliance_id);
		if (count($res) > 0)
			$json = $res['json'];
		else
		{
			$req = $this->db->query('SELECT `id`, `avatar`, welcome_text, citation, nb_membre, tag, nom, date_start, inscription, description, url_forum FROM `alliance` WHERE `id` = "'.$alliance_id.'"');
			$json = json_encode($req->row);
			if ($this->users->isLogged() && $_SESSION['user']['alliance_id'] == $alliance_id)
				$needToSave = true;
		}
		$this->instance[$alliance_id] = new alliance($json);
        if ($needToSave)
        	self::saveRedis($this->instance[$alliance_id]->getDatas());
		return $this->instance[$alliance_id];
	}

}
?>
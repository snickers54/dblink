<?php
include_once('dblink_objects/ressource.php');
class ressources {
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
	public function take($obj, $array)
	{
		// verifie que les ressources existent et sont suffisantes
		foreach ($array AS $key => $value)
			if ($obj->$key === FALSE || $obj->$key < $value)
				return false;
		foreach ($array AS $key => $value)
			$obj->$key -= $value;
		self::save($obj);
		return true;
	}

	public function reset($planet_id)
	{
		$this->db->query('UPDATE `ressources` SET `metaux` = "'.START_METAUX.'", `cristaux` = "'.START_CRISTAUX.'", `population` = "'.START_POPULATION.'", `energie` = "'.START_ENERGIE.'", `tetranium` = "'.START_TETRANIUM.'",
								`cristaux_grow` = "'.START_CRISTAUX_GROW.'", `metaux_grow` = "'.START_METAUX_GROW.'", `population_grow` = "'.START_POPULATION_GROW.'", `tetranium_grow` = "'.START_TETRANIUM_GROW.'",
								`user_id` = -1, `limit_metaux` = "'.START_METAUX.'", `limit_cristaux` = "'.START_CRISTAUX.'", `limit_population` = "'.START_POPULATION.'", `limit_tetranium` = "'.START_TETRANIUM.'",
								`metaux_productivity` = 100, `cristaux_productivity` = 100, `tetranium_productivity` = 100 
								WHERE `planet_id` = "'.$planet_id.'"');
	}

	public function add($obj, $array)
	{
		foreach ($array AS $key => $value)
			if ($obj->$key === FALSE)
				return false;
		foreach ($array AS $key => $value)
			$obj->$key += $value;
		self::save($obj);
		return true;
	}

	// Permet de sauvegarder les donnees des ressources contenu dans $object en bdd et dans redis
	public function save($object)
	{
		self::saveBDD($object->getClass());
		self::saveRedis($object->getDatas());
	}

	// on sauvegarde les donnees des ressources contenu dans $vars dans redis
	public function saveRedis($vars)
	{
		$planet_id = $vars['id'];
		$json = json_encode($vars);
		$this->redis->hset("ressource_".$planet_id, "json", $json);
		$this->redis->expire("ressource_".$planet_id, 120);
	}
	// on sauvegarde les donnees des ressources contenu dans $vars en bdd
	public function saveBDD($vars)
	{
		$ressources_id = $vars['id'];
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
		$this->db->query('UPDATE `ressources` SET '.$sql.' WHERE id = "'.$ressources_id.'"');
	}
  // on recupere les donnees des ressources et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql	
	public function get($planet_id, $nocache = false)
	{
		if ($nocache == true)
			$this->instance = array();
		$needToSave = false;
		if (isset($this->instance[$planet_id]))
			return $this->instance[$planet_id];
		$res = $this->redis->hgetall("ressource_".$planet_id);
		if (count($res) > 0)
			$json = $res['json'];
		else
		{
			$req = $this->db->query('SELECT r.`id`, r.`user_id`, r.`planet_id`, r.`last_date`, r.`energie`, r.`metaux`, r.`cristaux`, 
											r.`population`, r.`tetranium`,
											r.`metaux_grow`, r.`cristaux_grow`, r.`population_grow`, r.`tetranium_grow`, r.`metaux_productivity`, 
											r.`cristaux_productivity`, r.`tetranium_productivity`, r.`limit_cristaux`, r.`limit_population`, 
											r.`limit_metaux`, r.`limit_tetranium`, r.`debris_cristal`, r.`debris_tetranium`, r.`debris_metal`,
											t.`race`
									FROM `ressources` r
										LEFT JOIN user t ON t.user_id = r.user_id
									WHERE r.`planet_id` = "'.$planet_id.'"');
			$race = $req->row['race'];
			unset($req->row['race']);
			$json = json_encode($req->row);
			if ($this->users->isLogged() && $_SESSION['user']['planet_id'] == $planet_id)
				$needToSave = true;
			$terrains = $this->db->query('SELECT pt.* FROM `planete_type_link` ptl LEFT JOIN `planete_type` pt ON pt.`id` = ptl.`id_planet_type` WHERE ptl.`id_planet` = "'.$planet_id.'"');
			$metaux = 0;
			$cristaux = 0;
			$tetranium = 0;
			if ($terrains->count > 0)
				foreach ($terrains->rows AS $t)
				{
					$metaux += $t['metal'];
					$cristaux += $t['cristal'];
					$tetranium += $t['tetranium'];
				}
			$terrains = array('metaux' => $metaux, 'cristaux' => $cristaux, 'tetranium' => $tetranium);
		}
		$r = new ressource($json);
		if (isset($race) && $race == "mineur")
			$r->bonusRaideur();
		if (isset($terrains))
			$r->growTerrains($terrains);
        $this->instance[$planet_id] = $r;
        if ($needToSave)
        	self::saveRedis($this->instance[$planet_id]->getDatas());
		return $this->instance[$planet_id];
	}

	public function run($obj)
	{
		$obj->active = "done";
		
		if ($obj->behavior_type == "resource_give" || $obj->behavior_type == "resource_give_ships")
		{
			$r = $this->ressources->get($obj->to_planet_id, true, true);
			$this->ressources->add($r, $obj->ressources);
			$obj->ressources = array();
		}

		if ($obj->behavior_type == "resource_ships" || $obj->behavior_type == "resource_give_ships")
		{
			$v = $this->vaisseaux->get($obj->to_planet_id, true, true);
			$this->vaisseaux->add($v, $obj->object);		
			$obj->object = array();
			$obj->action = MOVE_END;
		}
		return $obj;
	}
}
?>
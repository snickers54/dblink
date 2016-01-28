<?php
include_once('dblink_objects/batiment.php');
class batiments {
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

	public function reset($planet_id)
	{
		$array = array('mine_metal'=> array("level" => 1, "vie" => 0), 'mine_cristal'=> array("level" => 1, "vie" => 0),
						'puits_tetranium'=> array("level" => 1, "vie" => 0));
		$this->db->query('UPDATE `batiments` SET `batiments` = "'.$this->db->escape(json_encode($array)).'" WHERE `planet_id` = "'.$planet_id.'"');
	}

	public function refreshConstruction($planet_id)
	{
	    $ressource = $this->ressources->get($planet_id, true);
	    $batiment = self::get($planet_id, true);
	    $vaisseau = $this->vaisseaux->get($planet_id, false, true);
	    $technologie = $this->technologies->get($planet_id, true);
	    $defense = $this->defenses->get($planet_id, false, true);
	    $civil = $this->civils->get($planet_id, true);
	    $bool = false;
	    if ($batiment->checkConstructionsLevel($batiment))
	    {
	      $bool = true;
	      self::save($batiment);
	    }
	    if ($technologie->checkConstructionsLevel($technologie))
	    {
	      $bool = true;
	      $this->technologies->save($technologie);
	    }
	    if ($civil->checkConstructionsNumber($civil))
	    {
	      $bool = true;
	      $this->civils->save($civil);
	    }
	    if ($defense->checkConstructionsNumber($defense))
	      $this->defenses->save($defense);
	    if ($vaisseau->checkConstructionsNumber($vaisseau))
	      $this->vaisseaux->save($vaisseau);
	  	if ($bool)
	  	{
	    	self::updateDatas(array($batiment, $technologie, $civil, $defense), $ressource);
	    	$this->success->checkSuccess($this->users->get($batiment->user_id), $batiment, $vaisseau, $civil, $defense, $technologie);
	    }
	    unset($batiment, $technologie, $civil, $ressource, $defense, $vaisseau);
	    return $bool;
	}

	public function updateDatas($array_obj, $ressource)
	{
		$limit_cristal = START_CRISTAUX;
		$limit_metal = START_METAUX;
		$limit_population = START_POPULATION;
		$limit_tetranium = START_TETRANIUM;
		$obj = $array_obj[0];
		$energie_conso = 0;
		$energie_produit = 0;
		$datas = array_merge($obj->getDatas(), $array_obj[1]->getDatas(), $array_obj[2]->getDatas(), $array_obj[3]->getDatas());
		foreach ($datas AS $key => $b)
		{
			$energie_temp = 0;
			$b['number'] = (!isset($b['number'])) ? 0 : $b['number'];
			$rc = $b['ressources_change'];
			$b['level'] = (!isset($b['level'])) ? 0 : $b['level'];
			$energie_temp += $b['energie_base'] * $b['number'];
			$energie_temp += $b['energie_base'] * $b['level'];
			if ($b['level'] > 0)
				for ($i = 1; $i < $b['level']; $i++)
					$energie_temp += $energie_temp * (ENERGIE_FACTOR / 100);
			if ($rc == "m")
			{
				$energie_temp *= $ressource->metaux_productivity / 100;
				$ressource->metaux_grow = $obj->calculeGrowth(START_METAUX_GROW, 1 + (METAUX_GROW / 100), $b['level']);
			}
			if ($rc == "c")
			{
				$energie_temp *= $ressource->cristaux_productivity / 100;
				$ressource->cristaux_grow = $obj->calculeGrowth(START_CRISTAUX_GROW, 1 + (CRISTAUX_GROW / 100), $b['level']);
			}
			if ($rc == "t")
			{
				$energie_temp *= $ressource->tetranium_productivity / 100;
				$ressource->tetranium_grow = $obj->calculeGrowth(START_TETRANIUM_GROW, 1 + (TETRANIUM_GROW / 100), $b['level']);
			}
			$energie_conso += $energie_temp;
			if (preg_match("#^e:[0-9]{1,}$#", $rc))
			{
				$array = explode(":", $rc);
				$val = intval($array[1]);
				$energie_produit += $val * $b['number'];
			}
			if (preg_match("#^lp:[0-9]{1,}$#", $rc))
			{
				$array = explode(":", $rc);
			 	$val = intval($array[1]);
			 	$limit_population += $val * $b['number'];
			}
			if (preg_match("#^lm:[0-9]{1,}$#", $rc))
			{
			 	$array = explode(":", $rc);
			 	$val = intval($array[1]);
			 	$limit_metal += $val * $b['number'];
			}
			if (preg_match("#^lc:[0-9]{1,}$#", $rc))
			{
			 	$array = explode(":", $rc);
			 	$val = intval($array[1]);
			 	$limit_cristal += $val * $b['number'];
			}
			if (preg_match("#^lt:[0-9]{1,}$#", $rc))
			{
			 	$array = explode(":", $rc);
			 	$val = intval($array[1]);
			 	$limit_tetranium += $val * $b['number'];
			}
		}
		var_dump($energie_produit. " ". $energie_conso);
		exit;
		if (($ressource->energie = round($energie_produit - $energie_conso)) < 0)
		{
			if ($this->planetes->isCurrentlyUsed($ressource->planet_id) !== false)
				$this->users->addNotifRedis($this->template->language['notif_energie_low'], "energie", true, $ressource->user_id);//lancer une notification au joueur pour lui dire (s'il est connecte)
		}
		$ressource->limit_tetranium = $limit_tetranium;
		$ressource->limit_cristaux = $limit_cristal;
		$ressource->limit_metaux = $limit_metal;
		$ressource->limit_population = $limit_population;
		$this->ressources->save($ressource);
	}
	// checkRequirement
	public function checkRequirement($planet_id, $code)
	{
		$batiments = $this->batiments->get($planet_id);
		$vaisseaux = $this->vaisseaux->get($planet_id);
		$technologies = $this->technologies->get($planet_id);
		$defenses = $this->defenses->get($planet_id);
		$civils = $this->civils->get($planet_id);
		if ($batiments->$code)
			$bat = $batiments->$code;
		if ($vaisseaux->$code)
			$bat = $vaisseaux->$code;
		if ($defenses->$code)
			$bat = $defenses->$code;
		if ($technologies->$code)
			$bat = $technologies->$code;
		if ($civils->$code)
			$bat = $civils->$code;
		if (!$bat || strlen($bat['requirement']) == 0)
			return false;
		$requirement = explode(";", $bat['requirement']);
		$string = "";
		foreach ($requirement AS $req)
		{
			$array = explode(":", $req);
			$c = $array[0];
			$l = $array[1];
			if (($b = $batiments->$c) && (!isset($b['level']) || $b['level'] < $l))
				$string .= "- ".$b['nom']." ".((!isset($b['level'])) ? 0 : $b['level'])."/".$l."<br />";
			if (($v = $vaisseaux->$c) && (!isset($v['number']) || $v['number'] < $l))
				$string .= "- ".$v['nom']." ".((!isset($v['number'])) ? 0 : $v['number'])."/".$l."<br />";
			if (($d = $defenses->$c) && (!isset($d['number']) || $d['number'] < $l))
				$string .= "- ".$d['nom']." ".((!isset($d['number'])) ? 0 : $d['number'])."/".$l."<br />";
			if (($t = $technologies->$c) && (!isset($t['level']) || $t['level'] < $l))
				$string .= "- ".$t['nom']." ".((!isset($t['level'])) ? 0 : $t['level'])."/".$l."<br />";
		}
		return ($string == "") ? FALSE : $string;
	}

	// Permet de sauvegarder les donnees des batiments contenu dans $object en bdd et dans redis
	public function save($object)
	{
		self::saveBDD($object->getClass(), $object->planet_id);
		self::saveRedis($object->getClass(), $object->planet_id);
	}

	// on sauvegarde les donnees des batiments contenu dans $vars dans redis
	public function saveRedis($vars, $planet_id)
	{
		$json = json_encode($vars);
		$this->redis->hset("batiment_".$planet_id, "json", $json);
		$this->redis->expire("batiment_".$planet_id, 1800);
	}

	// on sauvegarde les donnees des batiments contenu dans $vars en bdd
	public function saveBDD($vars, $planet_id)
	{
		$this->db->query('UPDATE `batiments` SET batiments = "'.mysql_real_escape_string(json_encode($vars)).'" WHERE planet_id = "'.$planet_id.'"');
	}
  // on recupere les donnees des batiments et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql	
	public function get($planet_id, $nocache = false)
	{
		$needToSave = false;
		if ($nocache === true)
		{
			$this->instance = array();
			$this->redis->expire("batiment", 0);
		}
		if (isset($this->instance[$planet_id]))
			return $this->instance[$planet_id];
		$res = $this->redis->hgetall("batiment_".$planet_id);
		$ext = $this->redis->hgetall("batiment");
		if (count($ext) == 0)
		{
			$array = array();
			$query = $this->db->query('SELECT * FROM `batiments_type` WHERE `type` = "batiments"');
			foreach ($query->rows AS $val)
				$array[$val['code']] = $val;
			$ext = $array;
			$this->redis->hset("batiment", "json", json_encode($ext));
			$this->redis->expire("batiment", DAY);
		}
		else
			$ext = json_decode($ext['json'], true);
		if (count($res) > 0)
			$json = $res['json'];
		else
		{
			$req = $this->db->query('SELECT `batiments` FROM `batiments` WHERE `planet_id` = "'.$planet_id.'"');
			$json = $req->row['batiments'];
			if ($this->users->isLogged() && $_SESSION['user']['planet_id'] == $planet_id)
				$needToSave = true;
		}
		$this->instance[$planet_id] = new batiment($json, $ext, $planet_id);
        if ($needToSave)
        	self::saveRedis($this->instance[$planet_id]->getClass(), $planet_id);
		return $this->instance[$planet_id];
	}

}
?>
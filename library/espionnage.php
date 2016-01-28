<?php
class espionnage {
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

	public function isScanning($planet_id)
	{
		return $this->redis->hget("espionnage_scan", $planet_id);
	}

	public function checkScan()
	{
		$scans = $this->redis->hgetall("espionnage_scan");
		$time = time();
		foreach ($scans AS $planet_id => $val)
		{
			$array = json_decode($val, true);
			if ($time > $array['time_start'] + $array['time_action'])
			{
				// chercher et detruire les sondes qui n'ont pas eu de chance
				$sondes = self::getSpyEnnemie($planet_id);
				$array = array();
				foreach ($sondes AS $key => $s)
				{
					$chance_prox = self::calculChance($s['from_planet_id'], $planet_id);
					$chance = rand(0, 100);
					if ($chance > $chance_prox)
					{
						$array[] = $s;
						self::delete($s['id']);
					}
				}
				// creer le rapport avec $array
				$json = json_encode($array);
				$this->rapports->add($json, "scan", array('from_user_id' => $s['to_user_id'],
													'to_user_id' => $s['to_user_id'], 
													'from_planet_id' => $s['to_planet_id'], 
													'to_planet_id' => $s['to_planet_id']));
				$this->redis->hdel("espionnage_scan", $planet_id);
			}
		}
	}

	public function calculScanTime($planet_id)
	{
		$my = $this->batiments->get($planet_id);
	    $mytemp = $my->centre_espionnage;
	    $level = (!isset($mytemp['level'])) ? 0 : $mytemp['level'];
	    $time_scan = (2 * HOUR);
	    $time_scan -= (3 * $level / 100) * $time_scan;
	    return $time_scan;
	}

	public function launchScan($planet_id)
	{
		$time = self::calculScanTime($planet_id);
		$centre = $this->template->_batiments;
		$centre = $centre['centre_espionnage'];
		$level = (!isset($centre['level'])) ? 0 : $centre['level'];
		$time -= $time * ($level * 3 / 100);
		$json = json_encode(array("time_start" => time(), "time_action" => $time));
		$this->redis->hset("espionnage_scan", $planet_id, $json);
	}

	public function delete($id)
	{
		$this->db->query('UPDATE `deplacements` SET `active` = "done" WHERE `id` = "'.$id.'"');
	}

	public function calculChance($from_planet_id, $to_planet_id)
	{
		$from = $this->batiments->get($from_planet_id, true);
		$to = $this->batiments->get($to_planet_id, true);

		$from_level = (!isset($from->centre_espionnage['level'])) ? 0 : intval($from->centre_espionnage);
		$to_level = (!isset($to->centre_espionnage['level'])) ? 0 : intval($to->centre_espionnage);

		$pourcent = 50;
		if ($to_level == 0)
			$pourcent = 100;
		for ($i = 0; $i < $from_level; $i++)
			$pourcent += 2;
		for ($i = 0; $i < $to_level; $i++)
			$pourcent -= 2;
		return $pourcent;
	}

	public function getSpyEnnemie($to_planet_id)
	{
		$query = $this->db->query('SELECT d.*, p.galaxie, u.user_id, p.nom as planet_name, p.avatar, u.login FROM `deplacements` d 
									LEFT JOIN planete p ON p.id = d.to_planet_id
									LEFT JOIN user u ON u.user_id = d.to_user_id
							WHERE d.`type` = "espionnage" AND d.`active` = "action"
							AND d.`behavior_type` = "spy_stay" 
							AND d.`to_planet_id` = "'.$to_planet_id.'"');
		if ($query->count > 0)
			return $query->rows;
		return NULL;
	}

	public function getSpyActive($from_planet_id, $to_planet_id = NULL)
	{
		$filter = "";
		if ($to_planet_id !== NULL)
			$filter = 'AND d.`to_planet_id` = "'.$to_planet_id.'"';
		$query = $this->db->query('SELECT d.*, p.galaxie, u.user_id, p.nom as planet_name, p.avatar, u.login FROM `deplacements` d 
									LEFT JOIN planete p ON p.id = d.to_planet_id
									LEFT JOIN user u ON u.user_id = d.to_user_id
							WHERE d.`type` = "espionnage" AND d.`active` = "action"
							AND d.`behavior_type` = "spy_stay" 
							AND d.`from_planet_id` = "'.$from_planet_id.'" '.$filter);
		if ($query->count > 0)
			return $query->rows;
		return NULL;
	}

	public function rapport($planet_id, $advanced = false)
	{
	    $array['vaisseaux'] = $this->vaisseaux->get($planet_id, true, true)->getDatas();
	    $array['defenses'] = $this->defenses->get($planet_id, true, true)->getDatas();
	    $array['civils'] = $this->civils->get($planet_id, true, true)->getDatas();
	    if ($advanced == true)
	    {
	    	$array['batiments'] = $this->batiments->get($planet_id, true, true)->getDatas();
	    	$array['technologies'] = $this->technologies->get($planet_id, true, true)->getDatas();
	    }
	    $report_json = json_encode($array);
	    return $report_json;
	}

	public function run($obj)
	{
		$obj->active = "action";
		if ($obj->behavior_type == "spy_back")
			$obj->active = "done";


		$chance_prox = self::calculChance($obj->from_planet_id, $obj->to_planet_id);
      	$chance = rand(0, 100);
		if ($chance <= $chance_prox)
		{
			$json = self::rapport($obj->to_planet_id);
			$this->rapports->add($json, "espionnage", array('from_user_id' => $obj->from_user_id,
													'to_user_id' => $obj->to_user_id, 
													'from_planet_id' => $obj->from_planet_id, 
													'to_planet_id' => $obj->to_planet_id));
			$this->users->addStats($this->users->get($obj->from_user_id), "espionnage_success", 1);

		}
		else
		{
			$json = json_encode(array('failed' => 'true'));
			$this->rapports->add($json, "espionnage", array('from_user_id' => $obj->from_user_id,
													'to_user_id' => $obj->to_user_id, 
													'from_planet_id' => $obj->from_planet_id, 
													'to_planet_id' => $obj->to_planet_id));
			$json = json_encode(array('findout' => 1));
			$this->rapports->add($json, "espionnage", array('from_user_id' => $obj->from_user_id,
													'to_user_id' => $obj->to_user_id, 
													'from_planet_id' => $obj->from_planet_id, 
													'to_planet_id' => $obj->to_planet_id));
			$this->users->addStats($this->users->get($obj->from_user_id), "espionnage_fail", 1);
			$this->users->addStats($this->users->get($obj->to_user_id), "espionnage_avoid", 1);
			$obj->active = "done";
		}
		return $obj;
	}
}
?>
<?php 
class	stats
{
  // debut prologue LIB
	private $class;
	private $sorted = array();
	
	public function	__construct($class)
	{

	}

	public function loadLib($class) {
	if (is_array($class))
	  foreach ($class AS $key => $value)
	     $this->$key = $value;
	}
	public function __get($key)
	{
		return (isset($this->class[$key])) ? $this->class[$key] : NULL;
	}

	public function __set($key, $val)
	{
		$this->class[$key] = $val;
	}
	// fin prologue lib

	public function addRedis($key, $stats)
	{
		$json = json_encode($stats);
		$this->redis->zadd("statistiques", $key, $json);
	}

	public function addArray($user_id, $stats)
	{
		$stats['total'] = $stats['batiments'] + $stats['vaisseaux'] + $stats['technologies'] + $stats['civils'] + $stats['defenses'];
		$stats['user_id'] = $user_id;
		$this->sorted[] = $stats;
	}

	public function deleteAll()
	{
		$this->redis->expire("statistiques", 0);		
	}

	public function calculStats($obj)
	{
		$points = 0;
		foreach ($obj->getDatas() AS $code => $b)
		{
			if (!isset($b['level']) && !isset($b['number']))
				continue;
			$factor = (isset($b['level'])) ? $b['level'] : $b['number'];
			if ($b['type'] == "batiments" || $b['type'] == "technologies")
			{
				$masse = 0;
				for ($i = 1; $i < $factor; $i++)
				{
					$array = $obj->constructionRessources(array('metaux' => $b['metaux_base'], 
																'cristaux' => $b['cristaux_base'],
																'population' => $b['population_base'],
																'tetranium' => $b['tetranium_base']), $b['cost_augmentation'], $i, 1);
					$masse += $array['metaux'] + ($array['cristaux'] * 2) + ($array['tetranium'] * 5);
				}
			}
			else
				$masse = ($b['metaux_base'] + ($b['cristaux_base'] * 2) + ($b['tetranium_base'] * 5)) * $factor;
			$points += intval($masse / STATS_VALUE);
		}
		return $points;
	}

	public static function sort($a, $b) {return $b['total'] - $a['total'];}

	public function get($position)
	{
		$position--;
		$range = self::getRange($position, $position);
		return $range[0];
	}

	public function getMultiple($array)
	{
		$res = array();
		$bool = false;
		foreach ($array AS $user_id)
			if ($user_id == $_SESSION['user']['user_id'])
				$bool = true;
		if ($bool === false)
			$array[] = $_SESSION['user']['user_id'];
		foreach ($array AS $user_id)
		{
			$obj = $this->users->get($user_id, false);
			$pos = $this->users->getValueConfig($obj, "stats_position");
			$res[] = self::get($pos);
		}
		usort($res, "stats::sort");
		return $res;
	}

	public function getRange($start = "-inf", $end = "+inf")
	{		
		$array = $this->redis->zrangebyscore("statistiques", $start, $end);
		if (!count($array))
		{
			self::updateStats();
			$array = $this->redis->zrangebyscore("statistiques", $start, $end);
		}
		foreach ($array AS $key => $val)
			$array[$key] = json_decode($val, true);
		return $array;
	}

	public function count()
	{
		return $this->redis->zcard("statistiques");
	}

	public function updateStats()
	{
		$this->sorted = array();
		$users = $this->db->query('SELECT `user_id` FROM `user`');
		$users = $users->rows;
		$res = $this->redis->hget("config", "last_stats");
		if (!$res || ((time() - $res) > 8 * HOUR))
		{
			self::deleteAll();
			foreach ($users AS $user)
			{
				$user_id = $user['user_id'];
				$planets = $this->db->query('SELECT `id` FROM `planete` WHERE `user_id` = "'.$user_id.'" AND `active` = "1"');
				$planets = $planets->rows;
				$stats = array('batiments' => 0, 'vaisseaux' => 0, 'defenses' => 0, 'technologies' => 0, 'civils' => 0);
				foreach ($planets AS $planet_id)
				{
					$obj = $this->batiments->get($planet_id['id'], true);
					$stats['batiments'] += self::calculStats($obj);
					$obj = $this->civils->get($planet_id['id'], true);
					$stats_civils = self::calculStats($obj);
					$stats['civils'] += $stats_civils;
					$obj = $this->defenses->get($planet_id['id'], false, true);
					$stats['defenses'] += self::calculStats($obj);
					$obj = $this->technologies->get($planet_id['id'], true);
					$stats['technologies'] += self::calculStats($obj);
					$obj = $this->vaisseaux->get($planet_id['id'], false, true);
					$stats['vaisseaux'] += self::calculStats($obj);
					$ressources = $this->ressources->get($planet_id['id'], true);
					$ressources->updatePopulationGrowth($stats_civils);
					$this->ressources->save($ressources);
					$ressources = NULL;
				}
				self::addArray($user_id, $stats);
			}
			usort($this->sorted, "stats::sort");
			foreach ($this->sorted AS $key => $val)
			{
				$position = $key + 1;
				$user_id = $val['user_id'];
				$obj = $this->users->get($user_id, false);
				$val['login'] = $obj->login;
				$val['position'] = $position;
				$this->users->setValueConfig($obj, "stats_position", $position);
				self::addRedis($key, $val);
			}
			$this->redis->hset("config", "last_stats", time());
		}
	}
}
?>
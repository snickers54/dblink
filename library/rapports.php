<?php
class rapports {
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

	public function add($json, $template, $ids)
	{
		$this->db->query('INSERT INTO `rapports` SET `date` = "'.time().'", json = "'.mysql_real_escape_string($json).'", `template` = "'.$template.'", `from_user_id` = "'.$ids['from_user_id'].'", `to_user_id` = "'.$ids['to_user_id'].'", `from_planet_id` = "'.$ids['from_planet_id'].'", `to_planet_id` = "'.$ids['to_planet_id'].'"');
		$lastid = $this->db->getLastId();
		$array = array('type' => $template);
		if ($this->redis->keys("user_".$ids['from_user_id']))
			$this->redis->hset("rapport_".$ids['from_user_id'], $lastid, json_encode($array));
		if ($this->redis->keys("user_".$ids['to_user_id']))
			$this->redis->hset("rapport_".$ids['to_user_id'], $lastid, json_encode($array));
		return $lastid;
	}

	public function delete($id)
	{
		$this->db->query('DELETE FROM `rapports` WHERE `id` = "'.$id.'"');
	}

	public function get($id)
	{
		$query = $this->db->query('SELECT r.`json`, r.`template`, r.`id`, r.`date`, r.`readed`, p1.`id` as planet_from, p2.`id` as planet_to, u1.user_id as user_from, u2.user_id as user_to, u1.alliance_id as alliance_from, u2.alliance_id as alliance_to
									FROM `rapports` r
										LEFT JOIN `user` u1 ON r.from_user_id = u1.user_id
										LEFT JOIN `user` u2 ON r.to_user_id = u2.user_id
										LEFT JOIN `planete` p1 ON r.from_planet_id = p1.id
										LEFT JOIN `planete` p2 ON r.to_planet_id = p2.id
									WHERE r.`id` = "'.$id.'"');
		if ($query->count)
		{
			$value = ($query->row['planet_to'] == $_SESSION['user']['planet_id']) ? 2 : 1;
			$query->row['user_from'] = $this->users->get($query->row['user_from'])->getDatas();
			$query->row['user_to'] = $this->users->get($query->row['user_to'])->getDatas();
			$query->row['planet_from'] = $this->planetes->get($query->row['planet_from'])->getDatas();
			$query->row['planet_to'] = $this->planetes->get($query->row['planet_to'])->getDatas();
			$query->row['readed'] = (($value != $query->row['readed'] && $query->row['readed'] != 3)) ? false : true;
			if ($query->row['alliance_from'] > 0)
				$query->row['alliance_from'] = $this->alliances->get($query->row['alliance_from'])->getClass();
			else
				unset($query->row['alliance_from']);
			if ($query->row['alliance_to'] > 0)
				$query->row['alliance_to'] = $this->alliances->get($query->row['alliance_to'])->getClass();
			else
				unset($query->row['alliance_to']);
			if ($query->row['readed'] == false)
				$this->readRapport($id, $value);
			return $query->row;
		}
		return NULL;
	}

	public function readRapport($id, $value)
	{
		$this->db->query('UPDATE `rapports` SET `readed` = `readed` + "'.$value.'" WHERE `id` = "'.$id.'"');
	}

	public function getNewRapports($planet_id)
	{
		// 0 => personne ne l'a lue, 1 => source l'a lue, 2 => victime l'a lue, 3 => tout le monde l'a lue
		$res = $this->db->query('SELECT COUNT(*) as nb_unread FROM `rapports` WHERE (`to_planet_id` = "'.$planet_id.'" AND `readed` < 2) OR (`from_planet_id` = "'.$planet_id.'" AND (`readed` = 0 OR `readed` = 2)) ');
		return $res->row['nb_unread'];
	}

	public function getPlanet($id)
	{
		$query = $this->db->query('SELECT r.`template`, r.`readed`, r.`id`, r.`date`, u1.`login` as user_from, u2.`login` as user_to, p1.`nom` as planet_from, p2.`nom` as planet_to, u1.avatar as avatar_user_from, u2.avatar as avatar_user_to, p1.avatar as avatar_planet_from, p2.avatar as avatar_planet_to
									FROM `rapports` r
										LEFT JOIN `user` u1 ON r.from_user_id = u1.user_id
										LEFT JOIN `user` u2 ON r.to_user_id = u2.user_id
										LEFT JOIN `planete` p1 ON r.from_planet_id = p1.id
										LEFT JOIN `planete` p2 ON r.to_planet_id = p2.id
									WHERE r.`from_planet_id` = "'.$id.'" OR (r.`to_planet_id` = "'.$id.'" AND r.`template` NOT IN ("espionnage", "colonisation"))');
		if ($query->count)
		{
			foreach ($query->rows AS $key => $val)
			{
				$value = ($query->rows[$key]['planet_to'] == $_SESSION['user']['planet_id']) ? 2 : 1;
				$query->rows[$key]['readed'] = (($value != $query->rows[$key]['readed'] && $query->rows[$key]['readed'] != 3)) ? false : true;
			}
			return $query->rows;
		}
		return NULL;		
	}

	private function getTemplate($template) {return "rapport_".$template.'.html';}

	public function generateHTML($rapport, $template)
	{
		$rapport['json'] = json_decode($rapport['json'], true);
		$view = self::getTemplate($template);
		$twig = $this->template->getTwig();
        ob_start();
      	echo $twig->render($view, array_merge($rapport, array('_lang' => $this->template->language, '_user' => $this->users->get($_SESSION['user']['user_id'])->getDatas())));
      	$html = ob_get_contents();
      	ob_end_clean();
      	return $html;
	}
}
?>
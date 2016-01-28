<?php 
class	mails
{
  // debut prologue LIB
	private $class;
	
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

	public function 	getOne($user_id, $mail_id)
	{
		$res = $this->db->query('SELECT ud.signature, u.avatar, u.login, m.from_user_id, m.id, m.date, m.objet, m.texte, m.id_parent, mp.statut, COUNT(mp2.id) as nb_new 
									FROM `message` m 
										LEFT JOIN `message_people` mp ON mp.id_message = m.id
										LEFT JOIN `user` u ON u.user_id = m.from_user_id 
										LEFT JOIN `user_description` ud ON u.user_id = ud.user_id
										LEFT OUTER JOIN `message` m2 ON m2.id_parent = m.id
										LEFT OUTER JOIN `message_people` mp2 ON m2.id = mp2.id_message AND mp2.statut = "new" AND mp2.user_id = "'.$user_id.'"
									WHERE mp.user_id = "'.$user_id.'"
										AND m.id = "'.$mail_id.'"
									GROUP BY m.id
									ORDER BY m.id DESC');
		if ($res->count == 1)
		{
			$res->row['receiver'] = self::getPeople($res->row['id']);
			$res->row['texte'] = template::bbcode(htmlspecialchars($res->row['texte']));
			return $res->row;
		}
		return NULL;
	}

	public function 	updateStatut($user_id, $mail_id, $statut = "read")
	{
		$where = "";
		$where2 = "";
		if ($statut == "read")
		{
			$where = "mp.statut = 'new' AND ";
			$where2 = "mp2.statut = 'new' AND ";
		}
		else if ($statut == "del")
		{
			$where = "mp.statut != 'del' AND ";
			$where2 = "mp2.statut != 'del' AND ";
		}
		$query = $this->db->query('SELECT mp.`id` 
									FROM `message_people` mp
									WHERE '.$where.' mp.`id_message` = "'.$mail_id.'"
										AND mp.user_id = "'.$user_id.'"
									UNION SELECT mp2.id
								   	FROM `message` m 
								   		LEFT JOIN `message_people` mp2 ON m.id = mp2.id_message
								   	WHERE '.$where2.' m.id_parent = "'.$mail_id.'"
								   		AND mp2.user_id = "'.$user_id.'"');
		if ($query->count > 0)
		{
			$in = "";
			$i = 0;
			foreach ($query->rows AS $val)
			{
				if ($i > 0)
					$in .= ",";
				$in .= $val['id'];
				$i++;
			}
			$this->db->query('UPDATE `message_people` SET `statut` = "'.$statut.'" WHERE id IN('.$in.')');
		}
	}

	public function 	getLast($user_id)
	{
		$res = $this->db->query('SELECT u.login, m.date, m.id, m.objet, CONCAT(SUBSTRING(m.`objet`, 1, 30), "...") as objet_preview
								FROM `message_people` mp 
									LEFT JOIN `message` m ON m.id = mp.id_message
									LEFT JOIN `user` u ON u.user_id = m.from_user_id
								WHERE mp.statut = "read" and mp.user_id = "'.$user_id.'" ORDER BY m.`id` DESC LIMIT 3');
		if ($res->count > 0)
			return $res->rows;
		return NULL;		
	}

	public function 	getNew($user_id)
	{
		$res = $this->db->query('SELECT u.login, m.date, m.id, m.objet, CONCAT(SUBSTRING(m.`objet`, 1, 30), "...") as objet_preview
								FROM `message_people` mp 
									LEFT JOIN `message` m ON m.id = mp.id_message
									LEFT JOIN `user` u ON u.user_id = m.from_user_id
								WHERE mp.statut = "new" and mp.user_id = "'.$user_id.'"');
		if ($res->count > 0)
			return $res->rows;
		return NULL;
	}

	public function 	get($user_id)
	{
		$res = $this->db->query('SELECT u.avatar, u.login, m.from_user_id, m.id, m.date, m.objet, m.texte, m.id_parent, mp.statut, COUNT(mp2.id) as nb_new, COUNT(m2.id) as children 
								FROM `message` m 
									LEFT JOIN `message_people` mp ON mp.id_message = m.id
									LEFT JOIN `user` u ON u.user_id = m.from_user_id 
									LEFT OUTER JOIN `message` m2 ON m2.id_parent = m.id
									LEFT OUTER JOIN `message_people` mp2 ON m2.id = mp2.id_message AND mp2.statut = "new" AND mp2.user_id = "'.$user_id.'"
								WHERE mp.user_id = "'.$user_id.'"
									AND m.id_parent IS NULL
									AND mp.statut != "del"
								GROUP BY m.id
								ORDER BY m.id DESC, nb_new DESC');
		if ($res->count > 0)
			return $res->rows;
		return NULL;
	}

	public function 	getSended($user_id)
	{
		$res = $this->db->query('SELECT u.avatar, u.login, m.from_user_id, m.id, m.date, m.objet, m.texte, m.id_parent, mp.statut, COUNT(mp2.id) as nb_new 
								FROM `message` m 
									LEFT JOIN `message_people` mp ON mp.id_message = m.id
									LEFT JOIN `user` u ON u.user_id = m.from_user_id 
									LEFT OUTER JOIN `message` m2 ON m2.id_parent = m.id
									LEFT OUTER JOIN `message_people` mp2 ON m2.id = mp2.id_message AND mp2.statut = "new" AND mp2.user_id = "'.$user_id.'"
								WHERE mp.user_id = "'.$user_id.'"
									AND m.id_parent IS NULL
									AND mp.statut != "del"
									AND m.from_user_id != "'.$user_id.'"
								GROUP BY m.id
								ORDER BY m.id DESC, nb_new DESC');
		if ($res->count > 0)
			return $res->rows;
		return NULL;
	}

	public function 	getPeople($mail_id)
	{
		$res = $this->db->query('SELECT u.avatar, u.login, u.user_id, m.statut
								 FROM message_people m
								 	LEFT JOIN user u ON u.user_id = m.user_id
								 	LEFT JOIN message m2 ON m2.id = m.id_message
								 WHERE m.id_message = "'.$mail_id.'" AND m.user_id != m2.from_user_id
								 ORDER BY u.login ASC');
		if ($res->count > 0)
			return $res->rows;
		return NULL;
	}

	public function 	getChildren($user_id, $mail_id)
	{
		$res = $this->db->query('SELECT ud.signature, u.avatar, u.login, m.id, m.from_user_id, m.date, m.objet, m.texte, m.id_parent, mp.statut
								 FROM `message` m
								 	LEFT JOIN `message_people` mp ON mp.id_message = m.id
								 	LEFT JOIN `user` u ON u.user_id = m.from_user_id
								 	LEFT JOIN `user_description` ud ON u.user_id = ud.user_id
								 WHERE mp.user_id = "'.$user_id.'"
								 	AND mp.statut != "del"
								 	AND m.id_parent = "'.$mail_id.'"
								ORDER BY m.id DESC');
		if ($res->count > 0)
		{
			foreach ($res->rows AS $key => $val)
			{
				$res->rows[$key]['receiver'] = self::getPeople($val['id']);
				$res->rows[$key]['texte'] = template::bbcode(htmlspecialchars($val['texte']));
			}
			return $res->rows;
		}
		return NULL;
	}

	public function 	add($from_user_id, $objet, $texte, $list_users_id, $id_parent = NULL)
	{
		$parent = "";
		if ($id_parent !== NULL)
			$parent = ", id_parent = ".intval($id_parent);
		$this->db->query('INSERT INTO `message` SET 
							`objet` = "'.$this->db->escape($objet).'", 
							`texte` = "'.$this->db->escape($texte).'",
							`from_user_id` = "'.$from_user_id.'",
							`date` = NOW()
							'.$parent);
		$lastid = $this->db->getLastId();
		$user = $this->users->get($_SESSION['user']['user_id']);
		foreach ($list_users_id AS $id)
		{
			if ($this->redis->keys("user_".$id))
				$this->redis->hset("messagerie_".$id, $lastid, json_encode(array('date' => time(),
																	'id' => $lastid,
																	'objet' => $this->db->escape($objet),
																	'objet_preview' => substr($objet, 0, 30)."...",
																	'login' => $user->login)));
			$this->db->query('INSERT INTO `message_people` SET
								`id_message` = "'.$lastid.'",
								`user_id` = "'.$id.'",
								`statut` = "new"');
		}
		$this->db->query('INSERT INTO `message_people` SET
								`id_message` = "'.$lastid.'",
								`user_id` = "'.$from_user_id.'",
								`statut` = "read"');
	}

}
?>
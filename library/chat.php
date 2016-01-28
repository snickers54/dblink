<?php 
class	chat
{
  // debut prologue LIB
	private $class;
	private $commandArray = array('/say ' => 'commandSay', '/help' => 'commandHelp',
									'/me ' => 'commandMe', '/token ' => 'commandToken');
	
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

	public function addBDD($user_id, $msg, $channel, $timestamp)
	{
		$this->db->query('INSERT INTO `chat` SET `user_id` = "'.$user_id.'", msg ="'.$this->db->escape($msg).'", `channel` = "'.$channel.'", `timestamp` = "'.$timestamp.'"');
		return $this->db->getLastId();
	}

	public function addRedis($user_id, $msg, $channel, $bdd_id, $timestamp)
	{
		$user = $this->users->get($user_id);
		$json = json_encode(array('id' => $bdd_id,
								 	'user_id' => $user_id,
								 	'msg' => $msg,
								 	'preview' => substr($msg, 0, 30)."...",
								 	'timestamp' => $timestamp, 
								 	'channel' => $channel,
								 	'login' => $user->login,
								 	'avatar' => $user->avatar));
		$this->redis->zadd($channel, $bdd_id, $json);
		$number = $this->redis->zcount($channel, '-inf', '+inf');
		if ($number > 50)
			$this->redis->zremrangebyrank($channel, 0, $number - 50);
	}

	public function add($user_id, $msg, $channel)
	{
		$timestamp = time();
		$bdd_id = self::addBDD($user_id, $msg, $channel, $timestamp);
		self::addRedis($user_id, $msg, $channel, $bdd_id, $timestamp);
	}

	public function removeBdd($msg_id)
	{
		$this->db->query('DELETE FROM `chat` WHERE `id` = "'.$msg_id.'"');
	}

	public function removeRedis($channel, $msg_id)
	{
		$this->redis->zremrangebyscore($channel, $msg_id, $msg_id);
		$this->redis->hset($channel."_del", $msg_id, json_encode(array("msg_id" => $msg_id, "channel" => $channel)));
		$this->redis->expire("chat_del", 30);
	}

	public function remove($channel, $msg_id)
	{
		self::removeBDD($msg_id);
		self::removeRedis($channel, $msg_id);
	}

	public function getDel($channel)
	{
		return $this->redis->hgetall($channel."_del");
	}

	public function getRedis($channel, $higher_id)
	{
		$array = $this->redis->zrangebyscore($channel, $higher_id, '+inf');
		foreach ($array AS $key => $val)
			$array[$key] = json_decode($val, true);
		return array_reverse($array);
	}

	public function getBDD($channel, $limit = 30)
	{
		$query = $this->db->query('SELECT u.`login`, u.`avatar`, c.`id`, c.`user_id`, c.`msg`, CONCAT(SUBSTRING(c.`msg`, 1, 30), "...") as preview, c.`timestamp`, c.`channel` 
									FROM `chat` c
										LEFT JOIN `user` u ON u.`user_id` = c.`user_id`
									WHERE c.`channel` = "'.$channel.'" ORDER BY c.`id` DESC LIMIT '.$limit);
		if ($query->count > 0)
		{
			$user = $this->users->get($_SESSION['user']['user_id']);
			$user->chat_current_id = $query->rows[0]['id'] + 1;
			$this->users->saveRedis($user->getDatas());
			return $query->rows;
		}
		return NULL;
	}

	public function getOne($channel, $msg_id)
	{
		$query = $this->db->query('SELECT u.`login`, u.`avatar`, c.`id`, c.`user_id`, c.`msg`, CONCAT(SUBSTRING(c.`msg`, 1, 30), "...") as preview, c.`timestamp`, c.`channel` 
									FROM `chat` c
										LEFT JOIN `user` u ON u.`user_id` = c.`user_id`
									WHERE c.`channel` = "'.$channel.'" AND c.`id` = "'.$msg_id.'"');
		if ($query->count == 1)
			return $query->row;
		return NULL;
	}

	public function get($channel, $higher_id)
	{
		if ($higher_id !== NULL)
			return self::getRedis($channel, $higher_id);
		return self::getBDD($channel);
	}

	public function commandHelp($obj, $user)
	{
		$this->chat->add(-1, $this->template->language['help_commands'], "chat");
		return NULL;
	}

	public function commandToken($obj, $user)
	{
		$token = substr($obj->texte, 7);
		// dans ce cas on cree le token
		if ($this->users->isAdmin($user))
		{
			$nb = (intval($token) <= 0) ? 1 : intval($token);
			for ($i = 0; $i < $nb; $i++)
				$this->tokens->create($obj->user_id);
		}
		// la on tente de valider un token
		else
		{
			$retour = $this->tokens->valid($obj->user_id, $token);
			if ($retour === NULL)
				$this->chat->add(-1, $this->template->language['token_unknown'], "chat");
			if ($retour === FALSE)
				$this->chat->add(-1, $this->template->language['token_timeout'], "chat");
		}		
		return NULL;
	}

	public function commandMe($obj, $user)
	{
		$obj->texte = "[b]* ".$user->login." ".substr($obj->texte, 4)." *[/b]";
		$obj->user_id = 0;
		return $obj;
	}

	public function commandSay($obj, $user)
	{
		if ($this->users->isAdmin($user) || $this->users->isModo($user))
		{
			$obj->user_id = -1;
			$obj->texte = "[b]".substr($obj->texte, 5)."[/b]";
			return $obj;
		}
		return NULL;
	}

	public function checkCommands($obj, $user)
	{
		$obj->texte = trim($obj->texte);
		foreach ($this->commandArray AS $key => $val)
			if (!strncmp($obj->texte, $key, strlen($key)))
				return $this->$val($obj, $user);
		return $obj;
	}

	public function loadUser($user_id, $array)
	{
		$u = $this->users->get($user_id);
		$array['login'] = $u->login;
		$array['avatar'] = $u->avatar;
  		$array['grade'] = $u->grade;
		if (($a = $u->getAlliance()))
			$array['ally_nom'] = $a->nom;
		return $array;
	}
}
?>
<?php
class tokens {
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
	// FIN PROLOGUE 

	public function clean()
	{
		$this->db->query('DELETE FROM `token` WHERE `id_user` = "0" AND `timestamp_end` < "'.time().'"');
	}

	public function valid($user_id, $token)
	{
		$res = $this->db->query('SELECT `id`, `dbgolds`, `timestamp_end` FROM `token` WHERE `token` = "'.$token.'" AND `id_user` = "0" LIMIT 1');
		if ($res->count == 0)
			return NULL;
		if ($res->row['timestamp_end'] < time())
			return FALSE;
		$this->db->query('UPDATE `token` SET `id_user` = "'.$user_id.'" WHERE `id` = "'.$res->row['id'].'"');
		$user = $this->users->get($user_id);
		$this->users->addDBGolds($user, $res->row['dbgolds']);
		$this->chat->add(-1, $user->login.$this->template->language['token_validated'], "chat");
		return TRUE;
	}

	public function create($user_id = -1)
	{
		$token = md5(SALT.time().rand(0, 999));
		$start = time();
		$end = ($start + rand(1000, 3600));
		$this->db->query('INSERT INTO `token` SET `dbgolds` = "'.rand(1, 3).'", `id_user` = "0", `token` = "'.$token.'", `timestamp_create` = "'.$start.'", `timestamp_end` = "'.$end.'"');
		$this->chat->add($user_id, $this->template->language['token_added']." ".$token.$this->template->language['token_validity'].template::convertTime($end - $start), "chat");
		return $token;
	}

	public function addRandom()
	{
		$res = $this->redis->hget("config", "last_token");
		if ((!$res || ((time() - $res) > 8 * HOUR)) && rand(0, 100) < 5)
		{
			$token = self::create(-1);
			$this->redis->hset("config", "last_token", time());
		}
	}
}
?>
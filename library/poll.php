<?php
class	poll
{
	// debut prologue LIB
	private $class;
	private $instance = array();

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
	 public function dispatchEvents()
  {
    $user = $this->users->get($_SESSION['user']['user_id'], false);
    $answer = array();
    // commencons par recuperer les messages venant du chat
    if ($this->users->getValueConfig($user, "chat_ban") == 0)
    {
      self::eventChat($user, $answer);
      self::eventDelChat($user, $answer);
    }
    self::waitingNotifications($user, $answer);
    // ici on verifie qu'on recupere pas la liste des connectes 
    // chaque fois qu'on passe dans la fonction mais bien toutes les 35 secondes
    $last_connected = $this->users->getValueConfig($user, "last_connected");
    if ($last_connected === FALSE || time() - $last_connected > 35)
    {
      if (!$user->race || !strlen($user->race))
        $answer['race'] = true;
      self::waitingSuccess($user);
      self::eventConnected($user, $answer);
      self::eventMP($user, $answer);
      self::eventRapport($user, $answer);
      self::eventDeplacements($user, $answer);
      $this->users->setValueConfig($user, "last_connected", time());
    }
    $my = array("my" => array("user_id" => $user->user_id, "isModo" => $user->isModo, "isAdmin" => $user->isAdmin));
    return (count($answer) > 0) ? array_merge($answer, $my) : NULL;
  }

  public function waitingNotifications($user, &$answer)
  {
    $notifs = $this->redis->hget("notifications_".($user->user_id), "json");
    $notifs = json_decode($notifs, true);
    if (count($notifs) > 0)
    {
      $answer['notifications'] = $notifs;
      $this->redis->expire("notifications_".$user->user_id, 0);
    }
  }

  public function waitingSuccess($user)
  {
    $res = $this->redis->hgetall("achievement_".$user->user_id);
    if (count($res) > 0)
    {
      foreach ($res AS $time => $success)
      {
        $success = json_decode($success, true);
        $this->template->setAchivement($success['msg'], $success['avatar'], $success['class']);
      }
      $this->redis->expire("achievement_".$user->user_id, 0);
    }
  }

  public function eventDeplacements($user, &$answer)
  {
    $mp = $this->redis->hgetall("deplacements_".$user->user_id);
    if ($mp && count($mp))
    {
      foreach ($mp AS $key => $m)
        $mp[$key] = json_decode($m, true);
      $answer['deplacements'] = $mp;
      $this->redis->expire("deplacements_".$user->user_id, 0);
      $array = array("_success_" => count($mp).$this->template->language['new_deplacements_notif']);
      $this->template->addJSON($array);
    }    
  }

  public function eventRapport($user, &$answer)
  {
    $mp = $this->redis->hgetall("rapport_".$user->user_id);
    if (count($mp))
    {
      foreach ($mp AS $key => $m)
        $mp[$key] = json_decode($m, true);
      $answer['rapport'] = $mp;
      $this->redis->expire("rapport_".$user->user_id, 0);
      $array = array("_success_" => count($mp).$this->template->language['new_rapport_notif']);
      $this->template->addJSON($array);
    }    
  }

  public function eventMP($user, &$answer)
  {
    $mp = $this->redis->hgetall("messagerie_".$user->user_id);
    if (count($mp))
    {
      foreach ($mp AS $key => $m)
        $mp[$key] = json_decode($m, true);
      $answer['mp'] = $mp;
      $this->redis->expire("messagerie_".$user->user_id, 0);
      $array = array("_success_" => count($mp).$this->template->language['success_receive_mp']);
      $this->template->addJSON($array);
    }
  }

  public function eventConnected($user, &$answer)
  {
    $friends = $user->friends;
    foreach ($friends AS $key => $val)
    {
      $user_id = $val['user_id'];
      $ttl = $this->redis->ttl('user_'.$user_id);
      if ($ttl > 0)
      {
        $u = $this->users->get($user_id);
        $answer['friends'][] = array('login' => $u->login, 'user_id' => $user_id, 'ping_color' => (($ttl < 10) ? "badge-warning" : "badge-success"));
      }
    }
  }

  public function eventChat($user, &$answer)
  {
    $msg_chats = $this->chat->get('chat', $user->chat_current_id);
    if ($msg_chats !== NULL && count($msg_chats) > 0)
    {
      foreach ($msg_chats AS $key => $val)
      {
        $msg_chats[$key] = $this->chat->loadUser($val['user_id'], $msg_chats[$key]);
        $msg_chats[$key]['msg'] = template::bbcode(htmlspecialchars($val['msg']));
        $msg_chats[$key]['msg_wbbcode'] = htmlspecialchars($val['msg']);
      }
      $answer['chat'] = $msg_chats;
      // normalement le [0] c'est l'id le plus grand
      $max = $user->chat_current_id;
      foreach ($msg_chats AS $key => $val)
        if ($val['id'] > $max)
          $max = $val['id'];
      $user->chat_current_id = $max + 1;
      $this->users->saveRedis($user->getDatas());
    }
  }

  public function eventDelChat($user, &$answer)
  {
  	$msg_del = $this->chat->getDel('chat');
  	if (count($msg_del) == 0)
  		return ;
  	$cookie = null;
  	if (isset($_COOKIE['del_chat']))
  		$cookie = json_decode($_COOKIE['del_chat'], true);
  	foreach ($msg_del AS $key => $msg)
  		if (!$cookie || !isset($cookie[$key]))
  			$answer['chat_del'][] = json_decode($msg, true);
    setcookie('del_chat', json_encode($msg_del), (time() + 30));
  }

  public function getBreak($user_id)
  {
    return $this->redis->keys("break_".$user_id);
  }

  public function addBreak($user_id)
  {
    $this->redis->hset("break_".$user_id, "break", "true");
    $this->redis->expire("break_".$user_id, 5);
  }

}
?>
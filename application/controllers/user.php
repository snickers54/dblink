<?php
class userController extends controller
{
  public function indexAction()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("index");
    $this->template->setView("index_user");
    if (isset($_GET['user_id']))
      $user = $this->users->get($_GET['user_id']);
    else
      $user = $this->users->get($_SESSION['user']['user_id']);

    if (($a = $user->getAlliance()))
      $this->template->profil_alliance = $a->getdatas();
    $profil = $user->getDatas();
    $profil['filleuls'] = self::checkAvatars($this->model->getFilleuls($user->user_id));
    $profil['amis'] = self::checkAvatars($this->model->getFriends($user->user_id));
    if (count($profil['amis']) > 0)
      $this->success->add($user, "amitie", 1);
    if (count($profil['amis']) >= 5)
      $this->success->add($user, "amitie", 2);
    if (count($profil['amis']) >= 9)
      $this->success->add($user, "amitie", 3);
    if (count($profil['amis']) >= 10)
      $this->success->add($user, "amitie_important", 1);
    if (count($profil['amis']) >= 50)
      $this->success->add($user, "amitie_important", 2);
    if (count($profil['amis']) >= 100)
      $this->success->add($user, "amitie_important", 3);

    $this->template->grades = $this->grades->get();
    $this->template->success = $this->success->get($user);
    $this->template->profil = $profil;
  }


  public function changeRace()
  {
    $this->users->needLogin();
    if (!isset($_POST['race']) || !in_array($_POST['race'], array('mineur', 'raideur', 'explorateur')))
      $this->template->redirect($this->template->language['error_race_unknown'], TRUE, "");
    $user = $this->users->get($_SESSION['user']['user_id']);
    $user->race = $_POST['race'];
    $this->users->save($user);
    $this->template->redirect($this->template->language['success_race_selection'].$_POST['race'], false, "");
  }

  public function lostpasswordAction()
  {
    $this->template->loadLanguage("index");
    $this->template->setView("lostPassword");
    $this->template->generate = captchme_generate_html(CAPTCHME_PUBLIC);
    if (isset($_POST['email']))
    {
      $email = $_POST['email'];
      if (isset($_POST["captchme_response_field"])) {
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        $resp = captchme_verify(CAPTCHME_PRIVATE,
          $_POST["captchme_challenge_field"],
          $_POST["captchme_response_field"],
          $remoteIp,
          CAPTCHME_AUTH);
        if (!$resp->is_valid)
          $this->template->redirect($this->template->language['check_captcha_fail'], TRUE, "SELF");
        if ($resp->is_valid && ($time === FALSE || ($time - time()) <= 0))
        {
          if ($this->users->lostPassword($email))
            $this->template->redirect($this->template->language['lostpassword_successfull'], FALSE, "/index/index");
          $this->template->redirect($this->template->language['email_unknown'], TRUE, "SELF");
        }
      }
    }
  }

  // MAILS 
  public function mailsAction()
  {
    $this->users->needLogin();
    $this->template->setView("mail_list");
    $this->template->loadLanguage("user");
    $this->template->mails = $this->mails->get($_SESSION['user']['user_id']);
  }

  public function mailAction()
  {
    $this->users->needLogin();
    $this->template->setView("mail");
    $this->template->loadLanguage("user");
    $this->template->mp = $this->mails->getOne($_SESSION['user']['user_id'], $_GET['id']);
    $this->template->children = $this->mails->getChildren($_SESSION['user']['user_id'], $_GET['id']);
    if (!isset($_GET['id']) || $this->template->mp == NULL)
      $this->template->redirect($this->template->language['mail_noexist'], TRUE, "/user/mails");
    $this->mails->updateStatut($_SESSION['user']['user_id'], $_GET['id']);
  }

  public function mailNewAction()
  {
    $this->users->needLogin();
    $this->template->setView("mail_new");
    $this->template->loadLanguage("user");
    if (isset($_GET['author']))
      $user_id = $_GET['author'];
    if (isset($user_id) && ($user = $this->users->get($user_id)))
      $this->template->mail = array("receiver" => array(array("login" => $user->login, "user_id" => $user->user_id)));
  }

  public function mailAnswerAction()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("user");
    $this->template->setView("mail_new_form");

    if (!isset($_GET['id_parent']))
      $this->template->redirect($this->template->language['mail_send_error'], TRUE, "");
    if (!($mail = $this->mails->getOne($_SESSION['user']['user_id'], $_GET['id_parent'])))
      $this->template->redirect($this->template->language['mail_send_error'], TRUE, "");
    $this->template->mail = $mail;
  }

  public function mailSend()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("user");

    if (!isset($_POST['author']) || !isset($_POST['object']) || !isset($_POST['mp']))
      $this->template->redirect($this->template->language['mail_send_error'], TRUE, "");
    if (count($_POST['author']) == 0)
      $this->template->redirect($this->template->language['mail_send_error_authors'], TRUE, "");
    if (strlen($_POST['object']) == 0)
      $this->template->redirect($this->template->language['mail_send_error_object'], TRUE, "");      

    $authors = $_POST['author'];
    $msg = $_POST['mp'];
    $object = $_POST['object'];

    $this->mails->add($_SESSION['user']['user_id'], $object, $msg, $authors, (isset($_POST['id_parent'])) ? $_POST['id_parent'] : NULL);
    $this->template->redirect($this->template->language['mail_send_success'], FALSE, "");
  }

  public function mailManage()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("user");
    if (!isset($_POST['type']) || !isset($_POST['id']))
      $this->template->redirect($this->template->language['mail_type_unknown'], TRUE, "");
    $id = intval($_POST['id']);
    if ($_POST['type'] == "delete")
    {
      $this->mails->updateStatut($_SESSION['user']['user_id'], $id, "del");
      $this->template->redirect($this->template->language['mail_delete_success'], FALSE, "");
    }
  }


  //
  public function completion()
  {
    $this->users->needLogin();
    // check si la completion qui est en cache (redis) est encore la ou non
    $users = $this->redis->hget('completion', 'users');
      // on remplis le cache
    if ($users == NULL)
    {
      $users = json_encode($this->model->getUsersList());
      $this->redis->hset('completion', 'users', $users);
      $this->redis->expire('completion', 1800);
    }
    $this->template->addJSON(array('users' => json_decode($users, true)));
  }

  private function checkAvatars($array)
  {
    if (is_array($array))
    foreach ($array AS $key => $val)
      $array[$key]['avatar'] = users::checkAvatar($val['avatar']);
    return $array;
  }

  public function editUser()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("index");
    $nom = $this->POST['nom'];
    $prenom = $this->POST['prenom'];
    $background = $this->POST['background'];

    $user = $this->users->get($_SESSION['user']['user_id']);
    if (isset($this->POST['password']) && isset($this->POST['password2']))
    {
      $password = $this->POST['password'];
      $password2 = $this->POST['password2'];
      if ($password != $password2)
        $this->template->redirect($this->template->language['error_mdp2'], TRUE, "");
      $user->password = md5(SALT.$password);
    }
    $this->model->updateUserDescription($nom, $prenom, $background, $user->user_id);
    if (strlen($background) >= 500)
      $this->success->add($user, "role_play", 1);
    if (strlen($background) >= 1500)
      $this->success->add($user, "role_play", 2);
    if (strlen($background) >= 5000)
      $this->success->add($user, "role_play", 3);

    $user->nom = $nom;
    $user->prenom = $prenom;
    $user->background = $background;
    $this->users->save($user);
    $this->template->redirect($this->template->language['modif_success'], FALSE, "");
  }

  public function changeLogin()
  {
    $this->users->needLogin();
    $login = $this->POST['login'];
    $this->template->loadLanguage("index");
    $user = $this->users->get($_SESSION['user']['user_id']);
    if ($user->login == $login)
      $this->template->redirect($this->template->language['login_dont_change'], TRUE, "");
    if (!preg_match("#^[a-zA-Z0-9-_]{3,}$#", $login))
      $this->template->redirect($this->template->language['error_login'], TRUE, "");
    if (!$this->model->checkLoginExist($login))
      $this->template->redirect($this->template->language['error_check_login'], TRUE, "");
    if (!$this->users->takeDBGolds($user, 30))
      $this->template->redirect($this->template->language['not_enough_dbgolds'], TRUE, "");
    $user->login = $login;
    $this->users->save($user);
    $this->template->redirect($this->template->language['modif_success'], FALSE, "");
  }

  public function editPlanet()
  {
    $this->users->needLogin();
    $nom = $this->POST['name'];
    $notes = $this->POST['notes'];

    $planet = $this->planetes->get($_SESSION['user']['planet_id']);
    $planet->note = $notes;
    $planet->nom = $nom;
    foreach ($_SESSION['list_planets'] AS $key => $val)
      if ($val['id'] == $_SESSION['user']['planet_id'])
        $_SESSION['list_planets'][$key]['nom'] = $nom;
    $this->planetes->save($planet);
    $this->template->redirect($this->template->language['modif_success'], FALSE, ""); 
  }

  public function changeAvatarPlanet()
  {
    $this->users->needLogin();
    $user = $this->users->get($_SESSION['user']['user_id']);
    $planet = $user->getPlanet();
    if (isset($this->POST['image']))
    {
      if (!file_exists("public/images/planete/".$this->POST['image']))
        $this->template->redirect($this->template->language['error_planet_avatar_noexist'], TRUE, "");
      if (isset($this->POST['buy']))
      {
        if (!$this->users->takeDBGolds($user, 10))
          $this->template->redirect($this->template->language['not_enough_dbgolds'], TRUE, "");
        $user->planetes_images[] = $this->POST['image'];
        $this->users->save($user);
      }
      if (!in_array($this->POST['image'], json_decode($user->planetes_images, true)))
        $this->template->redirect($this->template->language['error_planet_avatar_nohave'], TRUE, "");
      $planet->avatar = $this->POST['image'];
      $this->planetes->save($planet);
      $this->template->redirect($this->template->language['modif_success'], FALSE, "");
    }
  }
  public function refreshConstruction()
  {
    $this->users->needLogin();
    $planet_id = $_SESSION['user']['planet_id'];

    return $this->batiments->refreshConstruction($planet_id);
  }

  public function refreshRessources()
  {
    $this->users->needLogin();
    $bool = self::refreshConstruction();
    $planet = $this->planetes->get($_SESSION['user']['planet_id']);
    $user = $this->users->get($_SESSION['user']['user_id']);
    $ressources = $this->ressources->get($_SESSION['user']['planet_id'], $bool);
    $ressources->refresh($planet->weather);
    $this->template->_timestamp = time();
    //$this->template->_ressources = $ressources->getDatas();
    $this->template->setView("block_planet_left");
    $this->ressources->saveRedis($ressources->getDatas());
  }

  public function refreshLeft()
  {
    $this->users->needLogin();
    $ressources = $this->ressources->get($_SESSION['user']['planet_id']);
    $this->template->ressources = $ressources;
    $user = $this->users->get($_SESSION['user']['user_id']);
    if (($banned = $this->users->getValueConfig($user, "chat_ban")) == 0)
      $this->getLastChat();
    else
      $this->template->banned_time = $banned;
    $this->template->setView("block_left");
  }

  public function changePlanet()
  {
    $this->users->needLogin();
    $planet_id = intval($_GET['planet_id']);
    $planete = $this->planetes->get($planet_id);
    // on verifie que la planet appartient bien a ce joueur ...
    if ($planete->user_id != $_SESSION['user']['user_id'])
      $this->template->redirect($this->template->language['error_change_planet'], TRUE, "");
    // on set en session la valeur de l'id de la planet
    $_SESSION['user']['planet_id'] = $planet_id;
    // on get les ressources de cette planet
    $ressources = $planete->getRessources();
    // on les met a jours
    $user = $this->users->get($_SESSION['user']['user_id']);

    $ressources->refresh($planete->weather);
    // on remplace l'ancienne planete dans l'objet user par la nouvelle
    $user = $this->users->get($_SESSION['user']['user_id']);
    $user->setPlanet($planete);
    // on les envoie dans la vue
    $this->template->ressources = $ressources;
    $this->template->setView("block_planet_left");
    // on save la planet et les ressources
    $this->ressources->saveRedis($ressources->getDatas());
    $this->planetes->saveRedis($planete->getDatas());    
  }

  public function statsAction()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("user");
    $this->template->setView("statistiques");
    $me = $this->users->get($_SESSION['user']['user_id']);
    $mypos = $this->users->getValueConfig($me, "stats_position");
    if (isset($_GET['page']))
      $page = (intval($_GET['page']) < 1) ? 0 : intval($_GET['page']);
    else
      $page = intval($mypos / 30);
    $this->template->current_page = $page;
    $this->template->max_page = $this->stats->count() / 30;
    $start = $page * 30;
    $end = $start + 30;
    if ($mypos < $start || $mypos > $end)
      $this->template->mystats = $this->stats->get($mypos);
    $this->template->stats = $this->stats->getRange($start, $end);
  }

  public function statsCompare()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("user");
    $this->template->setView("stats_content");
    $this->template->stats = $this->stats->getMultiple($_GET['comparer']);
  }

  public function loginAction()
  {
    $this->template->loadLanguage("user");
  	if ($this->users->isLogged() == TRUE)
      $this->template->redirect("", FALSE, "/user/index");
  	// on verifie et on connecte le mec
    if (isset($this->GET['login']))
    {
      $this->POST['login'] = $this->GET['login'];
      $this->POST['password'] = $this->GET['password'];
    }
  	if (isset($this->POST['login']) && isset($this->POST['password']) &&
		    $this->users->connect($this->POST['login'], $this->POST['password']))
      $this->template->redirect($this->template->language['login_success'].$this->POST['login'], FALSE, "/index/index");
    $this->template->redirect($this->template->language['login_fail'], TRUE, "/index/news");
  }

  public function logoutAction()
  {
  	$this->users->disconnect();
  }

  // ICI LES FONCTIONS EN RAPPORT AVEC LE POLLING ET LE CHAT 
  public function poll()
  {

    session_write_close(); // prevents locking
        // If output buffering hasn't been setup yet...
    if(count(ob_list_handlers()) < 2) {
        // Buffer output, but flush always after any output
        ob_start();
        ob_implicit_flush(true);
        
        // Set the headers such that our response doesn't get cached
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }
    set_time_limit(30);
    $this->users->needLogin();
    $factor = 0;
    $start = time();
    $answer = NULL;
    do
    {
      $answer = $this->poll->dispatchEvents();
      for ($time = 2.5 + min($factor * 1.5, 7.5); $time >= 1; $time -= 0.5)
      {
        if ($this->poll->getBreak($_SESSION['user']['user_id']))
          break;
        usleep(500); 
      }
      $factor++;
    }
    while (!$answer && time() - $start < 30);
    if ($answer)
      $this->template->addJSON($answer);
    $this->template->addJSON(array());
  }

  public function delete()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("user");
    // on verifie qu'il a le droit de supprimer le message
    if (!isset($_POST['id']) || !isset($_POST['channel']))
      $this->template->redirect($this->template->language['mail_send_error'], TRUE, "");
    $user = $this->users->get($_SESSION['user']['user_id']);
    if (!($msg = $this->chat->getOne($_POST['channel'], $_POST['id'])))
      $this->template->redirect($this->template->language['mail_noexist'], TRUE, "");
    if ($user->user_id != $msg['user_id'] && !$this->users->isAdmin($user) && !$this->users->isModo($user))
      $this->template->redirect($this->template->language['not_enough_rights'], TRUE, "");
    $this->chat->remove($_POST['channel'], $_POST['id']);
  }

  public function send()
  {
    // #TODO verifier que le mec est pas banni
    $this->users->needLogin();
    if (isset($_POST['msg']))
    {
      foreach ($_POST['msg'] AS $array)
      {
        $std = (object) $array;
        $std->user_id = $_SESSION['user']['user_id'];
        $user = $this->users->get($std->user_id);
        if (($std = $this->chat->checkCommands($std, $user)) !== NULL)
          $this->chat->add($std->user_id, $std->texte, $std->channel);
      }
      $this->poll->addBreak($_SESSION['user']['user_id']);
    }
  }
}
?>

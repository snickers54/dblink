<?php
include_once('dblink_objects/user.php');

class	users
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
  public function save($object)
  {
    self::saveBDD($object->getClass());
    self::saveRedis($object->getDatas());
  }


  public function takeDBGolds($obj, $value)
  {
    if ($value > 0 && $obj->argent >= $value)
      {
        $obj->argent -= $value;
        self::save($obj);
        return true;
      }
    return false;
  }

  public function addDBGolds($obj, $value)
  {
    if ($value > 0)
      $obj->argent += $value;
    self::save($obj);
  }


  public function addNotifRedis($message, $type, $isError, $user_id)
  {
    $isError = ($isError === true) ? "_error_" : "_success_";
    $var = array('type' => $type, $isError => $message);
    $json = $this->redis->hget("notifications_".$user_id, "json");
    if ($json !== false)
      $json = json_decode($json, true);
    $json[time()] = $var;
    $this->redis->hset("notifications_".$user_id, "json", json_encode($json));
  }

  // on sauvegarde les donnees des ressources contenu dans $vars dans redis
  public function saveRedis($vars)
  {
    $user_id = $vars['user_id'];
    foreach ($vars AS $key => $val)
      if (in_array($key, user::$var_json))
        $vars[$key] = json_encode($val);
    $json = json_encode($vars);
    $this->redis->hset("user_".$user_id, "json", $json);
    $this->redis->expire("user_".$user_id, 60);
  }
  // on sauvegarde les donnees des ressources contenu dans $vars en bdd
  public function saveBDD($vars)
  {
    $user_id = $vars['user_id'];
    unset($vars['user_id']);
    $sql = "";
    $i = 0;
    foreach ($vars AS $key => $val)
    {
      if (in_array($key, user::$var_json))
        $val = json_encode($val);
      if ($i > 0)
        $sql .= ", ";
      $sql .= "{$key}='{$val}'";
      $i++;
    }
    $this->db->query('UPDATE `user` SET '.$sql.' WHERE user_id = "'.$user_id.'"');
  }

  public function set($user)
  {
    $this->instance[$user->user_id] = $user;
  }
 public static function checkAvatar($image)
 {
  $file = dirname(__FILE__)."/..".$image;
  if (strlen($image) <= 3 || !is_readable($file))
    return "/public/images/avatar/default.gif";
  return $image;
 }

 public function getStats($user, $key)
 {
  return (isset($user->stats[$key])) ? $user->stats[$key] : 0;
 }

 public function addStats($user, $key, $val)
 {
  if (!isset($user->stats[$key]))
    $user->stats[$key] = $val;
  else
    $user->stats[$key] += $val;
  self::save($user);
 }
  // on recupere les donnees des ressources et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql  
  // le 2e param sert a permettre la mise en memoire de l'element genere, 
  // cela posait probleme lors du poll qui gardait en memoire un objet qui avait peut etre evolue en bdd ou dans redis
  public function get($user_id, $cache = true)
  {
    $needToSave = false;
    if (isset($this->instance[$user_id]) && $cache == true)
      return $this->instance[$user_id];
    $res = $this->redis->hgetall("user_".$user_id);
    if (count($res) > 0)
      $json = $res['json'];
    else
    {
      if ($user_id > 0)
      {
        $req = $this->db->query('SELECT u.*, ud.nom, ud.prenom, ud.background, u.alliance_id FROM user u LEFT JOIN user_description ud ON (ud.user_id = u.user_id) WHERE u.user_id = "'.$user_id.'"');
        if ($req->count == 0)
          return NULL;
        $friends = $this->db->query('SELECT a.`user_id1` as "user_id" FROM `amis` a WHERE a.`active` = 1 AND a.`user_id2` = "'.$user_id.'" UNION SELECT b.`user_id2` AS "user_id" FROM `amis` b WHERE b.`active` = 1 AND b.`user_id1` = "'.$user_id.'" ');
        if ($friends->count > 0)
          $req->row['friends'] = $friends->rows;
      }
      elseif ($user_id == 0)
      {
        $req->row['login'] = "";
        $req->row['grade'] = "";
        $req->row['alliance_id'] = 0;
      }
      elseif ($user_id < 0)
      {
        $req->row['login'] = "Kosmix";
        $req->row['grade'] = "...";
        $req->row['alliance_id'] = 0;
        $req->row['avatar'] = "/public/images/avatar/robotdblink.jpg";
      }
      $json = json_encode($req->row);
      if (self::isLogged() && $_SESSION['user']['user_id'] == $user_id)
        $needToSave = true;
    }
    $alliance = NULL;
    $planet = NULL;
    $temp = json_decode($json, true);
    if ($temp['alliance_id'] > 0)
      $alliance = $this->alliances->get($temp['alliance_id']);
    if (isset($_SESSION['user']) && $user_id == $_SESSION['user']['user_id'])
      $planet = $this->planetes->get($_SESSION['user']['planet_id']);
    unset($temp);
    $this->instance[$user_id] = new user($json, $planet, $alliance);
    $user = $this->instance[$user_id];
    if (!$user->isModo || !$user->isAdmin)
    {
      $user->isModo = self::isModo($user);
      $user->isAdmin = self::isAdmin($user);
    }
    if (!$user->grade)
      $user->grade = $this->grades->get($user);
    if (!$user->experience)
      $user->experience = $this->success->calculExperience($user);
    if (!$user->nb_total_missions)
    {
      $user->nb_total_missions = count($this->missions->get());
      $user->percentage_missions = ($user->nb_missions * 100 / $user->nb_total_missions);
    }
    if (!$user->nb_total_success)
    {
      $user->nb_total_success = (count($this->success->get()) > 0) ? count($this->success->get()) : 1;
      $user->percentage_success = ($user->nb_success * 100 / $user->nb_total_success);
    }
    $this->success->checkStats($user);
    if ($needToSave)
      $this->users->saveRedis($this->instance[$user_id]->getDatas());
    return $this->instance[$user_id];
  }

  public function delete($user_id)
  {
    $this->redis->expire("user_".$user_id, 0);
    $query = $this->db->query('SELECT `id` FROM `planete` WHERE `user_id` = "'.$user_id.'"');
    foreach ($query->rows AS $value)
    {
      $planet_id = $value['id'];
      $this->redis->expire("ressource_".$planet_id, 0);
      $this->redis->expire("planete_".$planet_id, 0);
      $this->redis->expire("vaisseau_".$planet_id, 0);
      $this->redis->expire("batiment_".$planet_id, 0);
      $this->redis->expire("technologie_".$planet_id, 0);
      $this->redis->expire("civil_".$planet_id, 0);
      $this->redis->expire("defense_".$planet_id, 0);
      $this->planetes->reset($planet_id);
    }
    $this->db->query('DELETE FROM `user` WHERE `user_id` = "'.$user_id.'"');
  }

  public function activate($user_id)
  {
    $this->db->query('UPDATE `user` SET `active` = 1 WHERE `user_id` = "'.$user_id.'"');
  }

  // pour deconnecter l'utilisateur
  public function disconnect()
  {
    setcookie("remember_me", "", time() - 3600, '/');
    $this->redis->expire("user_".$_SESSION['user']['user_id'], 0);
    $this->redis->expire("ressource_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("planete_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("vaisseau_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("batiment_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("technologie_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("civil_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("defense_".$_SESSION['user']['planet_id'], 0);
    $this->redis->expire("alliance_".$_SESSION['user']['alliance_id'], 0);
    session_destroy();
    unset($_SESSION);
    $this->template->redirect($this->template->language['connexion_logout'], FALSE, "/index/index");
  }

  // fonction qui check si on est loggue, si ce n'est pas le cas elle redirige vers une page qui ne necessite pas de login
  public function       needLogin($check = 0)
  {
    if ($check == USER)
      if (!$this->isLogged())
      {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
       $this->template->redirect("", TRUE, "/index/index");
      }
    $user = $this->users->get($_SESSION['user']['user_id']);     
    if ($check == ADMIN)
      if (!$this->isAdmin($user))
      {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        $this->template->redirect("", TRUE, "/index/index");
      }
      if ($check == MODO)
          if (!$this->isModo($user) && !$this->isAdmin($user))
      {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        $this->template->redirect("", TRUE, "/index/index");
      }
    return FALSE;
  }
  //fonction de gestion de la config user
  public function  setValueConfig($user, $key, $value)
  {
    $config = $user->config;
    $config[$key] = $value;
    $user->config = $config;
    self::save($user);
  }

  public function getValueConfig($user, $key)
  {
    $config = $user->config;
    if (isset($config[$key]))
      return $config[$key];
    return FALSE;
  }
 
  public function migration()
  {
    // on clean deja tout
    $this->db->query('DELETE FROM `planete`');
    $this->db->query('DELETE FROM `planete_type_link`');
    $this->db->query('DELETE FROM `ressources`');
    $this->db->query('DELETE FROM `deplacements`');
    $this->db->query('DELETE FROM `rapports`');
    $this->db->query('DELETE FROM `token`');
    $this->db->query('DELETE FROM `bourse_actions`');
    $this->db->query('DELETE FROM `bonus`');
    $this->db->query('DELETE FROM `bonus_type`');
    $this->db->query('DELETE FROM `batiments_rapports`');
    $this->db->query('DELETE FROM `batiments`');
    $this->db->query('DELETE FROM `banque`');
    $this->db->query('DELETE FROM `amis`');
    $this->db->query('DELETE FROM `alliance`');
    $this->db->query('DELETE FROM `accords`');
    $this->db->query('DELETE FROM `alliance_actu`');
    $this->db->query('DELETE FROM `alliance_grade`');
    $this->db->query('DELETE FROM `alliance_news`');
    $this->db->query('DELETE FROM `alliance_status`');
    $this->db->query('DELETE FROM `chat`');
    $this->db->query('DELETE FROM `message`');
    $this->db->query('DELETE FROM `message_people`');
    //$this->db->query('DELETE FROM `user`');
    //$this->db->query('DELETE FROM `user_description`');

    // on importe les utilisateurs de l'autre bdd ..

    // on cree les planetes avec leurs terrains, leurs batiments, leur ressources et 
    // on genere 255 systemes solaires
    for ($sys_solaire = 0; $sys_solaire < 255; $sys_solaire++)
    {
      $x = 10;
      $y = 10;
      $terrains = $this->db->query('SELECT `id` FROM `planete_type`');
      $terrains = $terrains->rows;
      $max = rand(5, 8);
      for ($i = 0; $i < $max; $i++)
      {
        $dump_terrains = $terrains;
        $case = rand(200, 400);
        $angle = rand(1, 359);
        $avatar = $this->planetes->getAvatarPlanet();
        $this->db->query('INSERT INTO `planete` SET `active` = "-1", `avatar` = "'.$avatar.'", `user_id` = "-1", `x` = "'.$x.'", `y` = "'.$y.'", `galaxie` = "'.$sys_solaire.'", `angle` = "1", `case` = "'.$case.'"');
        $id_planet = $this->db->getLastId();
        $this->db->query('INSERT INTO `ressources` SET `user_id` = "-1", planet_id = "'.$id_planet.'", `metaux` = "'.START_METAUX.'", `cristaux` = "'.START_CRISTAUX.'", `population` = "'.START_POPULATION.'", `tetranium` = "'.START_TETRANIUM.'",
                         `metaux_grow` = "'.START_METAUX_GROW.'", `cristaux_grow` = "'.START_CRISTAUX_GROW.'", `population_grow` = "'.START_POPULATION_GROW.'", `tetranium_grow` = "'.START_TETRANIUM_GROW.'", `energie` = "'.START_ENERGIE.'",
                         `limit_metaux` = "'.START_METAUX.'", `limit_cristaux` = "'.START_CRISTAUX.'", `limit_population` = "'.START_POPULATION.'", `limit_tetranium` = "'.START_TETRANIUM.'",
                         `debris_metal` = 0, `debris_cristal` = 0, `debris_tetranium` = 0, `metaux_productivity` = 100, `cristaux_productivity` = 100, `tetranium_productivity` = 100');
        $json_empty = json_encode(array());
        $json_batiment = json_encode(array('mine_metal' => array('level' => 1),
                                           'mine_cristal' => array('level' => 1),
                                           'puits_tetranium' => array('level' => 1)));
        $this->db->query('INSERT INTO `batiments` SET `user_id` = "-1", `planet_id` = "'.$id_planet.'", `batiments` = "'.$this->db->escape($json_batiment).'",
                          `vaisseaux` = "'.$this->db->escape($json_empty).'", `defensif` = "'.$this->db->escape($json_empty).'", `technologie` = "'.$this->db->escape($json_empty).'", `civils` = "'.$this->db->escape($json_empty).'"');
        for ($j = 0; $j < 3; $j++)
        {
          $key_terrain = array_rand($dump_terrains);
          unset($dump_terrains[$key_terrain]);
          $this->db->query('INSERT INTO `planete_type_link` SET `id_planet` = "'.$id_planet.'", `id_planet_type` = "'.$terrains[$key_terrain]['id'].'"');
        }
        $x += 5;
        $y += 5;
      }
    }
    $users = $this->db->query('SELECT `user_id` FROM `user`');
    foreach ($users->rows AS $val)
    {
      $user_id = $val['user_id'];
      $this->planetes->giveToUser($user_id);
    }
  }

  public function connect($login, $pass)
  {
    $password = $pass;
    $pass = md5(SALT.$pass);
    $login = $this->db->escape($login);
    $query = $this->db->query('SELECT user_id, login, active, alliance_id FROM `user` WHERE (`login` = "'.$login.'" OR `email` = "'.$login.'") AND `password` = "'.$pass.'"');
    if ($query->count == 1 && $query->row['active'] == 1)
      {
        //self::migration();
        if (isset($_POST['cookie']))
        {
          $cookie_value = md5(md5(SALT.$pass));
          setcookie('remember_me', $cookie_value, (time() + DAY), '/');
          $this->db->query('UPDATE `user` SET connexion_cookie = "'.$cookie_value.'" WHERE `user_id` = "'.$query->row['user_id'].'"');
        }
        $_SESSION['user'] = $query->row;
        $req = $this->db->query('SELECT r.id as ressources_id, p.id as planet_id FROM planete p LEFT JOIN ressources r ON (r.planet_id = p.id) WHERE p.user_id = "'.$_SESSION['user']['user_id'].'" AND p.active = 1 LIMIT 1');
        $_SESSION['user']['planet_id'] = $req->row['planet_id'];
        if (count($req->rows) >= 3)
          $this->success->add($this->users->get($_SESSION['user']['user_id']), "astronaute", 1);
        if (count($req->rows) >= 6)
          $this->success->add($this->users->get($_SESSION['user']['user_id']), "astronaute", 2);
        if (count($req->rows) >= 9)
          $this->success->add($this->users->get($_SESSION['user']['user_id']), "astronaute", 3);
        $_SESSION['user']['ressources_id'] = $req->row['ressources_id'];
        $req = $this->db->query('SELECT `nom`, `id`,`galaxie` FROM `planete` WHERE `user_id` = "'.$_SESSION['user']['user_id'].'" AND `active` = 1');
        $_SESSION['list_planets'] = $req->rows;
        return $query->row['user_id'];
      }
    return FALSE;
  }

 public function cookieConnect()
  {
    if (!self::isLogged() && isset($_COOKIE['remember_me']))
    {
      $query = $this->db->query('SELECT `user_id`, active, alliance_id FROM `user` WHERE `connexion_cookie` = "'.$this->db->escape($_COOKIE['remember_me']).'"');
      if ($query->count == 1)
      {
        $_SESSION['user'] = $query->row;
        $req = $this->db->query('SELECT r.id as ressources_id, p.id as planet_id FROM planete p LEFT JOIN ressources r ON (r.planet_id = p.id) WHERE p.user_id = "'.$_SESSION['user']['user_id'].'" AND p.active = 1 LIMIT 1');
        $_SESSION['user']['planet_id'] = $req->row['planet_id'];
        $_SESSION['user']['ressources_id'] = $req->row['ressources_id'];
        $req = $this->db->query('SELECT `nom`, `id`, `galaxie` FROM `planete` WHERE `user_id` = "'.$_SESSION['user']['user_id'].'" AND `active` = 1');
        $_SESSION['list_planets'] = $req->rows;
      }
    }
  }

  public function isLogged()
  {
    if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])
      && $_SESSION['user']['user_id'] > 0)
      return TRUE;
    return FALSE;
  }
  
  public function isAdmin($user)
  {
     return $this->getValueConfig($user, 'isAdmin');
  }
 
  public function isModo($user)
  {
    return $this->getValueConfig($user, 'isModo');
  }

  public function lostPassword($email)
  {
    $newPass = self::generatePassword(6);
    $newHash = md5(SALT.$newPass);
    $query = $this->db->query('SELECT `login`, `user_id` FROM `user` WHERE `email` = "'.$email.'"');
    if ($query->count > 0)
      {
        $query = $query->row;
        $this->db->query('UPDATE `user` SET `password` = "'.$newHash.'" WHERE `user_id` = "'.$query['user_id'].'"');
        $message = "Bonjour ".$query['login']."<br />Voici votre nouveau Mot de Passe :<br />".$newPass;
        $objet = "DBLINK - Oublie mot de passe";
        $this->mail->sendMail($email, $objet, $message);
        return true;
      }
      return false;
  }
 
  public function  generatePassword($taille)
  {
    $wpas = "";
    $cars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $wlong = strlen($cars);
    srand( (double) microtime() * 1000000);
    for($i = 0; $i < $taille; $i++)
      {
        $wpos = rand(0,$wlong-1);
        $wpas = $wpas.substr($cars,$wpos,1);
      }
    return $wpas;
  }

}
?>
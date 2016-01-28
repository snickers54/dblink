<?php 
class	success
{
  // debut prologue LIB
	private $class;
	private $success;

  public function	__construct($class)
  {
  }

	public function loadLib($class) {
    if (is_array($class))
      foreach ($class AS $key => $value)
         $this->$key = $value;
    $res = $this->redis->hget("achievements", "json");
    if (count($res) > 0)
      $this->success = json_decode($res, true);
    else
    {
      $query = $this->db->query('SELECT * FROM `achievements`');
      $array = array();
      foreach ($query->rows AS $s)
        $array[$s['code']] = $s;
      $this->success = $array;
      $this->redis->hset("achievements", "json", json_encode($array));
      $this->redis->expire("achievements", 3600);
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

  public function get($user = NULL)
  {
    if ($user !== NULL)
    {
      $bool = false;
      $achievements = $user->achievements;
      foreach ($achievements AS $key => $val)
        if (isset($this->success[$key]))
        {
          $this->success[$key]['level'] = $val;
          $this->success[$key]['medal'] = self::getClassAvatarMedal($val);
        }
        else
        {
          $bool = true;
          unset($achievements[$key]);
        }
      if ($bool)
      {
        $user->achievements = $achievements;
        $this->users->save($user);
      }
    }
    return $this->success;
  }


  private function getClassAvatarMedal($level)
  {
    if ($level == 1)
      return "/public/images/medal_bronze.png";
    if ($level == 2)
      return "/public/images/medal_argent.png";
    if ($level == 3)
      return "/public/images/medal_gold.png";
    return NULL;
  }

  public function calculExperience($user)
  {
    $achievements = $user->achievements;
    $xp = 0;
    if (is_array($achievements))
    {
      foreach ($achievements AS $key => $a)
        if (isset($this->success[$key]['level']))
          $xp += $this->success[$key]['pts'] * $a['level'];
    }
    return $xp;
  }

  public function checkSuccess($user, $batiments, $vaisseaux, $civils, $defenses, $technologies)
  {
    self::checkSuccessLevel($user, $batiments, $technologies);
    self::checkSuccessNumber($user, $civils, $defenses, $vaisseaux);
  }
  
  public function checkSuccessNumber($user, $civils, $defenses, $vaisseaux)
  {
    if ($vaisseaux->count() > 1000)
      self::add($user, "raideur_fou", 1);
    if ($vaisseaux->count() > 10000)
      self::add($user, "raideur_fou", 2);
    if ($vaisseaux->count() > 10000)
      self::add($user, "raideur_fou", 3);
    
    if ($defenses->count() > 1000)
      self::add($user, "defense", 1);
    if ($defenses->count() > 10000)
      self::add($user, "defense", 2);
    if ($defenses->count() > 10000)
      self::add($user, "defense", 3);

    if (($cannons_mammouths = $defenses->canon_mammouth))
    {
      $nb = (isset($cannons_mammouths['number'])) ? $cannons_mammouths['number'] : 0;
      if ($nb > 100)
        self::add($user, "lourd", 1);
      if ($nb > 500)
        self::add($user, "lourd", 2);
      if ($nb > 1500)
        self::add($user, "lourd", 3);
    }
  }

  public function checkSuccessLevel($user, $batiments, $technologies)
  {
    $mine_metal = $batiments->mine_metal;
    $mine_cristal = $batiments->mine_cristal;
    if ($mine_metal['level'] > 10)
      self::add($user, "metalleux", 1);
    if ($mine_metal['level'] > 20)
      self::add($user, "metalleux", 2);
    if ($mine_metal['level'] > 40)
      self::add($user, "metalleux", 3);

    if ($mine_cristal['level'] > 10)
      self::add($user, "cristallographie", 1);
    if ($mine_cristal['level'] > 20)
      self::add($user, "cristallographie", 2);
    if ($mine_cristal['level'] > 40)
      self::add($user, "cristallographie", 3);
  }

  public function checkStats($user)
  {
    if ($this->users->getStats($user, "nb_espionnage") > 0)
      self::add($user, "espionnage", 1);
    if ($this->users->getStats($user, "nb_espionnage") >= 15)
      self::add($user, "espionnage", 2);
    if ($this->users->getStats($user, "nb_espionnage") >= 25)
      self::add($user, "espionnage", 3);

    if ($this->users->getStats($user, "nb_espionnage") >= 100)
      self::add($user, "voyeurisme", 1);
    if ($this->users->getStats($user, "nb_espionnage") >= 1000)
      self::add($user, "voyeurisme", 2);
    if ($this->users->getStats($user, "nb_espionnage") >= 5000)
      self::add($user, "voyeurisme", 3);

    if ($this->users->getStats($user, "espionnage_fail") >= 100)
      self::add($user, "espionnage_fail", 1);
    if ($this->users->getStats($user, "espionnage_fail") >= 1000)
      self::add($user, "espionnage_fail", 2);
    if ($this->users->getStats($user, "espionnage_fail") >= 5000)
      self::add($user, "espionnage_fail", 3);
    
    if ($this->users->getStats($user, "espionnage_success") >= 100)
      self::add($user, "espionnage_success", 1);
    if ($this->users->getStats($user, "espionnage_success") >= 1000)
      self::add($user, "espionnage_success", 2);
    if ($this->users->getStats($user, "espionnage_success") >= 5000)
      self::add($user, "espionnage_success", 3);

    if ($this->users->getStats($user, "nb_attack") > 0)
      self::add($user, "attaques", 1);
    if ($this->users->getStats($user, "nb_attack") >= 15)
      self::add($user, "attaques", 2);
    if ($this->users->getStats($user, "nb_attack") >= 25)
      self::add($user, "attaques", 3);

    if ($this->users->getStats($user, "nb_attack") >= 100)
      self::add($user, "big_warrior", 1);
    if ($this->users->getStats($user, "nb_attack") >= 1000)
      self::add($user, "big_warrior", 2);
    if ($this->users->getStats($user, "nb_attack") >= 5000)
      self::add($user, "big_warrior", 3);

    if ($this->users->getStats($user, "nb_ressource") >= 100)
      self::add($user, "fond_investissement", 1);
    if ($this->users->getStats($user, "nb_ressource") >= 1000)
      self::add($user, "fond_investissement", 2);
    if ($this->users->getStats($user, "nb_ressource") >= 5000)
      self::add($user, "fond_investissement", 3);

    if ($user->argent >= 1000)
      self::add($user, "riche", 1);      
    if ($user->argent >= 2500)
      self::add($user, "riche", 2);
    if ($user->argent >= 5000)
      self::add($user, "riche", 3);
  }

  public function add($user, $code, $level = 1)
  {
    $achievements = $user->achievements;
    if (!isset($achievements[$code]) || $achievements[$code] < $level)
    {
      $achievements[$code] = $level;
      $user->achievements = $achievements;
      $this->users->save($user);
      $this->chat->add($user->user_id, $user->login.$this->template->language['obtain_success'].$this->success[$code]['nom'], "notifs");
      if ($user->user_id == $_SESSION['user']['user_id'])
        $this->template->setAchievement($this->template->language['success_unblock'].$this->success[$code]['nom'],
                                        self::getClassAvatarMedal($level));
      else
        $this->redis->hset("achievement_".$user->user_id, time(), json_encode(array('msg' => $this->template->language['success_unblock'].$this->success[$code]['nom'],
                                                                                    'medal' => self::getClassAvatarMedal($level))));
    }
  }

  public function remove($user, $code)
  {
    $achievements = $user->achievements;
    if (isset($achievements[$code]))
    {
      unset($achievements[$code]);
      $user->achievements = $achievements;
      $this->users->save($user);
    }
  }


}
// fin prologue lib
?>
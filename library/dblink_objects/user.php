<?php
class user
{
	 private $class = array();
   private $extensions = array();
	 private $planet;
   private $alliance;
   private $json;
   public static $var_json = array("config", "attack", "achievements", "spy", "mission", "planetes_images", 'stats');
   private $structure = array('user_id', 'login', 'password',
                              'email', 'avatar', 'access',
                              'alliance_id', 'argent', 'date_register',
                              'last_date', 'ip_register', 'last_ip',
                              'active', 'spy', 'attack', 'planetes_images',
                              'parrain_id', 'achievements', 'mission', 'config', 'race');

 	  public function __get($key)
  	{
      return (isset($this->class[$key])) ? $this->class[$key] : ((isset($this->extensions[$key])) ? $this->extensions[$key] : NULL);
  	}

    public function __construct($string, $planet, $alliance)
    {
      $this->json = $string;
      $this->planet = $planet;
      $this->alliance = $alliance;

      $array = json_decode($string, true);
      foreach ($array AS $key => $value)
        if (in_array($key, $this->structure))
        {
          if (in_array($key, self::$var_json))
            $value = json_decode($value, true);
          $this->class[$key] = $value;
        }
        else
          $this->extensions[$key] = $value;
      $this->avatar = users::checkAvatar($this->avatar);
      if (!isset($this->extensions['nb_success']))
        $this->extensions['nb_success'] = self::countSuccess();
      if (!isset($this->extensions['nb_missions']))
        $this->extensions['nb_missions'] = self::countMissions();
      $this->extensions['ressources_time'] = (!isset($_COOKIE['ressources_time'])) ? 10000 : $_COOKIE['ressources_time'];
    }

    public function countMissions() {return count($this->mission);}

    public function countSuccess() {return count($this->achievements);}

    public function isFriend($user_id) {
      if (count($this->friends))
      foreach ($this->friends AS $friend_id)
        if ($friend_id['user_id'] == $user_id)
          return true;
        return false;
    }

  	public function __set($key, $val)
  	{
      if (isset($this->class[$key]))
    	 $this->class[$key] = $val;
      else
        $this->extensions[$key] = $val;
  	}

  	public function getDatas()
  	{
  		return array_merge_recursive($this->extensions, $this->class);
  	}
    
    public function getClass()
    {
      return $this->class;
    }
  	
    public function getPlanet()
  	{
  		return $this->planet;
  	}

  	public function setPlanet($planet)
  	{
  		$this->planet = $planet;
  	}

    public function getRessources() {return $this->planet->getRessources();}
    public function setRessources($ressources) {$this->planet->setRessources($ressources);}
    public function getAlliance() {return $this->alliance;}
    public function setAlliance($alliance) {$this->alliance = $alliance;}
    public function getJSON() {return $this->json;}
}
?>
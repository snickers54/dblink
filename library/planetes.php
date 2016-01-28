<?php
include_once('dblink_objects/planete.php');
class	planetes
{
  // DEBUT PROLOGUE LIB
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
  // FIN PROLOGUE LIB

  // determine si 2 planetes son hostiles / amis / neutre / 
  public function getStatus($planet1, $planet2) {
    if ($planet1->user_id == $planet2->user_id)
      return "moi";
    $user1 = $this->users->get($planet1->user_id);
    $user2 = $this->users->get($planet2->user_id);
    
    // on verifie qu'ils ne font pas partie de la meme alliance
    $alliance1 = $user1->getAlliance();
    $alliance2 = $user2->getAlliance();
    if (($alliance1 && $alliance2 && $alliance1->id == $alliance2->id))
      return "alliance";
    if ($user1->isFriend($user2->user_id))
      return "amis";

    // on verifie qu'ils ne font pas partie d'amis communs
    return "hostile";
  }

  // verifie qu'une planete est actuellement utilise par un joueur connecte
  public function isCurrentlyUsed($planet_id) {return $this->redis->keys("planete_".$planet_id);}

  // list les avatars des planetes du dossier ..
  public function listAvatarPlanets($user_id)
  {
    $user = $this->users->get($user_id);
    $images = $user->planetes_images;
    $dir = "public/images/planete/";
    if (is_dir($dir))
    {
      $fd = opendir($dir);
      while (($file = readdir($fd)))
       if ($file != "." && $file != ".." && $file != "200x200" && $file != "88x88" && $file != ".svn")
       {
         $p['image_small'] = "/".$dir."88x88/".$file;
         $p['image_big'] = "/".$dir.$file;
         $p['image'] = $file;
         $p['got'] = in_array($file, $images);
         $array[] = $p;
       }
       closedir($fd);
     }
     return $array;
  }

  // transforme l'enum de la meteo en label comprehensible
  public function labelWeathers($weather)
  {
    if ($weather)
      return $this->template->language['weather_'.$weather];
    return $this->template->language['weather_unknown'];
  }

  public function nbPlanetsPlayer($user_id)
  {
    $query = $this->db->query('SELECT * FROM `planete` WHERE `user_id` = "'.$user_id.'"');
    return $query->count;
  }

  //permet de sauvegarder la planete ($object) en BDD et dans redis
  public function save($object)
  {
    self::saveBDD($object->getClass());
    self::saveRedis($object->getDatas());
  }
  // permet de sauvegarder les donnees de la planete contenu dans $vars dans redis 
  public function saveRedis($vars)
  {
    // on recupere l'id de la planete
    $planet_id = $vars['id'];
    $json = json_encode($vars);
    // pour chaque valeur on set dans le hash la clef et la valeur
    $this->redis->hset("planete_".$planet_id, "json", $json);
    $this->redis->expire("planete_".$planet_id, 1700);
  }
  // permet de sauvegarder les donnees de la planete contenu dans $vars en BDD
  public function saveBDD($vars)
  {
    $planet_id = $vars['id'];
    unset($vars['id']);
    // parce que ces valeurs se calculent toutes seules en bdd ...
    unset($vars['y']);
    unset($vars['x']);
    unset($vars['angle']);
    unset($vars['weather']);
    $sql = "";
    $i = 0;
    foreach ($vars AS $key => $val)
    {
      $val = $this->db->escape($val);
      if ($i > 0)
        $sql .= ", ";
      $sql .= "`{$key}`='{$val}'";
      $i++;
    }
    $this->db->query('UPDATE `planete` SET '.$sql.' WHERE id = "'.$planet_id.'"');
  }
 
  public function giveToUser($user_id)
  {
    $query = $this->db->query('SELECT `id` FROM `planete` WHERE `active` = -1 AND `user_id` = -1 ORDER BY RAND() LIMIT 1');
    if ($query->count <= 0)
      return FALSE;
    $id_planet = $query->row['id'];
    $this->db->query('UPDATE `planete` SET `user_id` = "'.$user_id.'", `active` = 1 WHERE `id` = "'.$id_planet.'"');
    $this->db->query('UPDATE `ressources` SET `user_id` = "'.$user_id.'", `energie` = 0 WHERE `planet_id` = "'.$id_planet.'"');
    $this->db->query('UPDATE `batiments` SET `user_id` = "'.$user_id.'" WHERE `planet_id` = "'.$id_planet.'"');
  }

   public function getAvatarPlanet()
    {
        $array = array();
        $dir = "public/images/planete/";
        if (is_dir($dir))
          {
      $fd = opendir($dir);
      while (($file = readdir($fd)))
        if ($file != "." && $file != ".." && $file != "200x200" && $file != "88x88")
          $array[] = $file;
      closedir($fd);
      $number = rand(0, count($array) - 1);
      return $array[$number];
          }
        else
          return NULL;
    }

  // reset la planete et ses ressources
  public function reset($planet_id)
  {
    $this->ressources->reset($planet_id);
    $this->batiments->reset($planet_id);
    $this->civils->reset($planet_id);
    $this->vaisseaux->reset($planet_id);
    $this->defenses->reset($planet_id);
    $this->technologie->reset($planet_id);
    $this->db->query('UPDATE `planete` SET `nom` = "", `active` = -1, `note` = "", `ordre` = 0 WHERE `id` = "'.$planet_id.'"');
  }

   // on recupere les donnees de la planet et on instance l'objet en le remplissant avec ces memes donnees
  // on cherche d'abord a trouver les infos dans redis
  // si ce n'est pas concluant on fait une requete sql
  public function get($planet_id)
  {
    $needToSave = false;
    if (isset($this->instance[$planet_id]))
      return $this->instance[$planet_id];
    $res = $this->redis->hgetall("planete_".$planet_id);
    if (count($res) > 0)
      $json_planet = $res['json'];
    else
    {
      $query = $this->db->query('SELECT p.*, COALESCE(NULLIF(p.nom,""), CONCAT("G", p.galaxie,"P", p.id)) as nom FROM `planete` p WHERE p.`id` = "'.$planet_id.'" LIMIT 1');
      $terrains = $this->db->query('SELECT pt.* FROM `planete_type_link` ptl LEFT JOIN `planete_type` pt ON pt.`id` = ptl.`id_planet_type` WHERE ptl.`id_planet` = "'.$planet_id.'"');
      $query->row['terrains'] = $terrains->rows;

      if ($query->count == 0)
        return NULL;
      $json_planet = json_encode($query->row);
      if ($this->users->isLogged() && $_SESSION['user']['planet_id'] == $planet_id)
        $needToSave = true;
    }

    $array['batiments'] = $this->batiments->get($planet_id);
    $array['vaisseaux'] = $this->vaisseaux->get($planet_id);
    $array['technologie'] = $this->technologies->get($planet_id);
    $array['defensif'] = $this->defenses->get($planet_id);
    $array['civils'] = $this->civils->get($planet_id);

    $ressources = $this->ressources->get($planet_id);
    $this->instance[$planet_id] = new planete($json_planet, $ressources, $array);
    if ($needToSave)
      self::saveRedis($this->instance[$planet_id]->getDatas());
    return $this->instance[$planet_id];
  }
}
?>
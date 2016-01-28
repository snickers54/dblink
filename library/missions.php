<?php 
class missions
{
  // debut prologue LIB
  private $class;
  private $missions = array();

  public function __construct($class)
  {
  }

  public function loadLib($class) {
    if (is_array($class))
      foreach ($class AS $key => $value)
         $this->$key = $value;
    $res = $this->redis->hget("missions", "json");
    if (count($res) > 0)
      $this->missions = json_decode($res, true);
    else
    {
      $query = $this->db->query('SELECT * FROM `mission`');
      $array = array();
      foreach ($query->rows AS $s)
        $array[$s['id']] = $s;
      $this->missions = $array;
      $this->redis->hset("missions", "json", json_encode($array));
      $this->redis->expire("missions", 3600);
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
      $m = $user->mission;
    foreach ($m AS $key => $val)
      {
        $this->missions[$key]['status'] = $val['status'];
        $this->missions[$key]['start_time'] = $val['start_time'];
      }
    }
    return $this->missions;
  }

  public function addMission($user, $id_mission)
  {
    $this->success->add($user, "premier_pas", 2);
  }

  public function removeMission($user, $id_mission)
  {

  }
}
?>
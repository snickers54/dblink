<?php 
class	grades
{
  // debut prologue LIB
	private $class;
	private $grades;

  public function	__construct($class)
  {
  }

	public function loadLib($class) {
    if (is_array($class))
      foreach ($class AS $key => $value)
         $this->$key = $value;
    
    $res = $this->redis->hget("grades", "json");
    if (count($res) > 0)
      $this->grades = json_decode($res, true);
    else
    {
      $query = $this->db->query('SELECT * FROM `grade` ORDER BY `min_pts` ASC');
      $array = array();
      foreach ($query->rows AS $g)
        $array[$g['id']] = $g;
      $this->grades = $array;
      $this->redis->hset("grades", "json", json_encode(self::get()));
      $this->redis->expire("grades", 3600);
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
    if ($user == NULL)
      return $this->grades;
    if ($user->access == 0)
      $user->access = 1;
    return $this->grades[$user->access]['nom'];
  }
}
// fin prologue lib
?>
<?php
class	bourse
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

  public function buy($type, $user, $last)
  {
    $this->db->query('INSERT INTO `bourse_actions` SET `timestamp` = NOW(), `user_id` = "'.$user->user_id.'", `type` = "'.$type.'", `dbgolds` = "'.$last[$type].'"');
  }

  public function sell($id)
  {
    $this->db->query('UPDATE `bourse_actions` SET `active` = 0 WHERE `id` = "'.$id.'"');
  }

  public function getActionsFromUser($user)
  {
    $query = $this->db->query('SELECT * FROM `bourse_actions` WHERE `user_id` = "'.$user->user_id.'" AND `active` = 1');
    if ($query->count)
      return $query->rows;
    return NULL;
  }


  // un achat fait pareil mais c'est un event exterieur et en +
  // alors qu'une vente fait pareil mais en -
  // le random fait fluctuer le cout entre +0.07 dbgolds a -0.10
  public function refresh()
  {
      $last = self::getLast();
      $quotations = array('metal', 'cristal', 'tetranium', 'energie');
      foreach ($quotations AS $type)
        $last[$type] += (rand(-5, +5) / 100);
      self::save($last['metal'], $last['cristal'], $last['tetranium'], $last['energie']);
  }
  
  public function getRedis()
  {
    $array = $this->redis->zrangebyscore("bourse", '-inf', '+inf');
    if (!$array)
      return NULL;
    foreach ($array AS $key => $val)
      $array[$key] = json_decode($val, true);
    return $array;
  }

  public function getBDD()
  {
    $query = $this->db->query('SELECT `date` as "timestamp", `energie_quotation` as energie, `metal_quotation` as metal, `cristal_quotation` as cristal, `tetranium_quotation` as tetranium FROM `bourse` ORDER BY `date` ASC LIMIT 50');
    if ($query->count)
    {
      foreach ($query->rows AS $key => $q)
      {
        $query->rows[$key]['metal'] = floatval($q['metal']);
        $query->rows[$key]['tetranium'] = floatval($q['tetranium']);
        $query->rows[$key]['energie'] = floatval($q['energie']);
        $query->rows[$key]['cristal'] = floatval($q['cristal']);
        $query->rows[$key]['timestamp'] = intval($q['timestamp']);
      }
      return $query->rows;
    }
    return NULL;
  }

  public function getLast($code = NULL)
  {
    $array = array_reverse(self::get());
    if ($code != NULL)
      {
        $temp = $array[0];
        $array[0] = array('timestamp' => $temp['timestamp'], $code => $temp[$code]);
      }
    return $array[0];
  }

  public function get()
  {
    if (!($res = self::getRedis()))
    {
      $bdd = self::getBDD();
      foreach ($bdd AS $b)
        self::saveRedis($b['metal'], $b['cristal'], $b['tetranium'], $b['energie'], $b['timestamp']);
      return $bdd;
    }
    return $res;
  }

  public function save($metal, $cristal, $tetranium, $energie)
  {
    self::saveRedis($metal, $cristal, $tetranium, $energie);
    $res = $this->redis->hget("config", "last_save_bourse");
    if (!$res || ((time() - $res) > (4 * HOUR)))
    {
      $this->redis->hset("config", "last_save_bourse", time());
      self::saveBDD($metal, $cristal, $tetranium, $energie);
    }
  }

  private function saveRedis($metal, $cristal, $tetranium, $energie, $time = false)
  {
    if ($time === false)
      $time = time();
    $json = json_encode(array('energie' => $energie, 'metal' => $metal, 'cristal' => $cristal, 'tetranium' => $tetranium, 'timestamp' => $time));
    $this->redis->zadd("bourse", $time, $json);
    $number = $this->redis->zcount("bourse", '-inf', '+inf');
    if ($number > 50)
      $this->redis->zremrangebyrank("bourse", 0, $number - 50);
  }

  private function saveBDD($metal, $cristal, $tetranium, $energie)
  {
    $this->db->query('INSERT INTO `bourse` SET `metal_quotation` = "'.$metal.'", `cristal_quotation` = "'.$cristal.'", `tetranium_quotation` = "'.$tetranium.'", `energie_quotation` = "'.$energie.'", `date` = "'.time().'"');
  }
}

?>
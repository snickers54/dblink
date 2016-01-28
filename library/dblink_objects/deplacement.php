<?php 
class deplacement
{
	  private $class = array();
   	private $extensions = array();
   	public $move_id;
   	private $json;


	public function __construct($string, $move_id)
    {
      $this->json = $string;
      $this->move_id = $move_id;
      $array = json_decode($string, true);
      foreach ($array AS $code => $value)
        if ($code != "")
          $this->class[$code] = $value;
      $this->class['object'] = json_decode($this->class['object'], true);
      $this->class['ressources'] = json_decode($this->class['ressources'], true);
      $this->extensions['action'] = self::getAction();
    }

    private function getAction()
    {
      $current = time();
      $start = $this->class['start'];
      $time_go = $this->class['time_go'];
      $time_back = $this->class['time_back'];
      $time_action = $this->class['time_action'];

      if ($current < ($start + $time_go))
        return MOVE_GO;
      if ($current < ($start + $time_go + $time_action))
        return MOVE_ACTION;
      if ($current < ($start + $time_go + $time_action + $time_back))
        return MOVE_BACK;
      return MOVE_END;
    }

    public function __get($key)
    {
      return (isset($this->class[$key])) ? $this->class[$key] : ((isset($this->extensions[$key])) ? $this->extensions[$key] : NULL);
    }

    public function __set($key, $val)
    {
      if (isset($this->class[$key]))
       $this->class[$key] = $val;
      else
        $this->extensions[$key] = $val;
    }
    public function setExtensions($array)
    {
      foreach ($array AS $b)
        $this->extensions[$b['code']] = $b;
    }

    public function getExtensions()
    {
      return $this->extensions;
    }

  	public function getDatas()
  	{
  		return array_merge_recursive($this->extensions, $this->class);
  	}
    
    public function getClass()
    {
      return $this->class;
    }
  	
    public function getJSON()
    {
      return $this->json;
    }
}
?>
<?php 
include_once('building.php');
class civil extends building
{
	 private $class = array();
   private $extensions = array();
   public $_type = "civils";
   public $planet_id;
   private $json;

 	  public function __get($code)
  	{
      if (isset($this->class[$code]) && isset($this->extensions[$code]))
        return array_merge_recursive($this->class[$code], $this->extensions[$code]);
      if (isset($this->class[$code]) && !isset($this->extensions[$code]))
        return $this->class[$code];
      if (!isset($this->class[$code]) && isset($this->extensions[$code]))
        return $this->extensions[$code];
      return FALSE;
  	}

    public function __construct($string, $ext, $planet_id, $min)
    {
      $this->json = $string;
      $this->planet_id = $planet_id;
      $array = json_decode($string, true);
      foreach ($array AS $code => $value)
        if ($code != "")
          $this->class[$code] = $value;
      self::setExtensions($ext, $min);
    }


    public function __set($code, $val)
    {
      if (is_array($val))
        foreach ($val AS $key => $value)
        {
          if (isset($this->class[$code][$key]) || $key == "construction")
            $this->class[$code][$key] = $value;
          else
            $this->extensions[$code][$key] = $value;
        }
    }


    public function deleteConstruction($code)
    {
      if (isset($this->class[$code]['construction']))
      {
        $number = $this->class[$code]['construction']['number'];
        unset($this->class[$code]['construction']);
        return $number;
      }
      return 0;
    }
    
    public function getRandom() {
      if (count($this->class))
        return array_rand($this->class);
      return NULL;
    }

    public function addBuilding($code, $array)
    {
      $this->class[$code] = $array;
    }

    public function getConstruction()
    {
      $datas = self::getClass();
      $res = array();
      $extensions = self::getExtensions();
      foreach ($datas AS $code => $array)
        if (isset($array['construction']) && $array['construction'] !== NULL)
          $res[$code] = array('nom' => $extensions[$code]['nom'], 'code' => $code, 
            'number' => $array['construction']['number'],
            'time' => $array['construction']['time'] - (time() - $array['construction']['start']), 
            'totaltime' => $array['construction']['end'] - $array['construction']['start']);
      return $res;
    }


    public function setExtensions($array, $min)
    {
      $keys = array();
      foreach ($array AS $b)
      {
        $code = $b['code'];
        $keys[$code] = 1;
        if ($min === false ||
          ($min === true && isset($this->class[$code]) && $this->class[$code]['number'] > 0))
          $this->extensions[$code] = $b;
      }
      foreach ($this->class AS $code => $val)
        if (!array_key_exists($code, $keys))
          unset($this->class[$code]);
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
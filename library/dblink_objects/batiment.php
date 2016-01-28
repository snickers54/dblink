<?php 
include_once('building.php');
class batiment extends building
{
	 private $class = array();
   private $extensions = array();
   public $_type = "batiments";
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

    public function __construct($string, $ext, $planet_id)
    {
      $this->json = $string;
      $this->planet_id = $planet_id;
      $array = json_decode($string, true);
      foreach ($array AS $code => $value)
        if ($code != "")
          $this->class[$code] = $value;
      self::setExtensions($ext);
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
          $res[$code] = array('nom' => $extensions[$code]['nom'], 'code' => $code, 'level' => $array['level'] + 1,
            'time' => $array['construction']['time'] - (time() - $array['construction']['start']),
            'totaltime' => $array['construction']['end'] - $array['construction']['start']);
      return $res;
    }

    public function deleteConstruction($code)
    {
      if (isset($this->class[$code]['construction']))
      {
        unset($this->class[$code]['construction']);
        return $this->class[$code]['level'] + 1;
      }
      return 0;
    }

    public function setExtensions($array)
    {
      $keys = array();
      foreach ($array AS $b)
      {
        $this->extensions[$b['code']] = $b;
        $keys[$b['code']] = 1;
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
<?php 
include_once('building.php');
include_once('combat_specs.php');

class vaisseau extends building
{
	 private $class = array();
   private $extensions = array();
   public $_type = "vaisseaux";
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
      if (is_array($array))
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

    public function try_selection($code)
    {
      $specs = combat_specs::get($code);
      $keys = array_keys($this->class);
      if (!shuffle($keys) || count($keys) <= 0)
        return NULL;
      foreach ($keys AS $key)
        if ($this->class[$key]['number'] > 0 && isset($specs[$key]) && 
            rand(1, 100) <= $specs[$key]['selection'])
          return $key;
      return $keys[array_rand($keys)];
    }

    public function getRandom() {
      if (count($this->class))
        return array_rand($this->class);
      return NULL;
    }

    public function delete($code)
    {
      if (!isset($this->class[$code]['construction']))
      {
        unset($this->extensions[$code]);
        unset($this->class[$code]);
      }
    }

    public function count()
    {
      $nb = 0;
      foreach ($this->class AS $key => $array)
        if (isset($array['number']))
          $nb += $array['number'];
      return $nb;
    }

    public function prepareMove($array)
    {
      $ship = array();
      $stockage = 0;
      $tetranium = 0;
      foreach ($array AS $code => $number)
        if ($number > 0 && $this->$code['number'] <= $number)
        {
          $ship[$code] = array("number" => $number, "vie" => $this->$code['vie'], "attaque" => $this->$code['attaque']);
          $temp = $this->$code;
          $tetranium += $number * $temp['tetranium_base'];
          $stockage += $number * $temp['stockage_unite'];
        }
        return array('ship'=>$ship, 'stockage' => $stockage, "tetranium" => $tetranium);
    }

    public function getStockage()
    {
      $stockage = 0;
      foreach (self::getDatas() AS $code => $ship)
        if (isset($ship['number']) && $ship['number'] > 0)
          $stockage += $ship['number'] * $ship['stockage_unite'];
      return $stockage;
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

    public function getConstruction()
    {
      $datas = self::getClass();
      $res = array();
      $extensions = self::getExtensions();
      foreach ($datas AS $code => $array)
        if (isset($array['construction']) && $array['construction'] !== NULL)
          $res[$code] = array('nom' => $extensions[$code]['nom'], 'code' => $code, 'number' => $array['construction']['number'],
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
        $keys[$b['code']] = 1;
        if ($min === false ||
          ($min === true && isset($this->class[$code]) && $this->class[$code]['number'] > 0))
            $this->extensions[$code] = $b;
      }
      foreach ($this->class AS $code => $val)
        if (!array_key_exists($code, $keys))
          unset($this->class[$code]);
    }

    public function addBuilding($code, $array)
    {
      $this->class[$code] = $array;
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
<?php
class alliance 
{
	private $class = array();
  private $extensions = array();
  private $json;
  
  public function __construct($string)
  {
    $this->json = $string;
    $array = json_decode($string, true);
    foreach ($array AS $key => $value)
      $this->class[$key] = $value;
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
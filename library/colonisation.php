<?php
class colonisation {
	// DEBUT PROLOGUE LIB
	private $class;
	private $instance = array();

	public function	__construct($class)
	{

	}

	public function loadLib($class) 
	{
		if (is_array($class))
		{
			foreach ($class AS $key => $value)
				$this->$key = $value;
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
	// FIN PROLOGUE LIB

	public function booked($planet_id, $user_id)
	{
		$this->db->query('UPDATE SET `user_id` = "'.$user_id.'" FROM `planete` WHERE `id` = "'.$planet_id.'"');
	}

	public function run($obj)
	{
		$obj->active = "done";
		$this->db->query('UPDATE SET `active` = 1 FROM `planete` WHERE `id` = "'.$obj->to_planet_id.'"');
		$r = $this->ressources->get($obj->to_planet_id, true, true);
		$this->ressources->add($r, $obj->ressources);
		$v = $this->vaisseaux->get($obj->to_planet_id, true, true);
		$this->vaisseaux->add($v, $obj->object);
		$obj->ressources = array();
		$obj->object = array();
		if ($obj->behavior_type == "colonisation_technologie")
		{
			$t = $this->technologies->get($obj->from_planet_id);
			$t2 = $this->technologies->get($obj->to_planet_id);
			$t2->copy($t);
			$this->technologies->save($t2);
		}
		return $obj;
	}
}
?>
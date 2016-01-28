<?php
class recyclage {
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

	public function run($obj)
	{
		$obj->active = "done";
		// calculer le stockage total
		$vaisseaux = $this->vaisseaux->create($obj->object, true);
		$stockage = $vaisseaux->getStockage();

		$ressources = $this->ressources->get($obj->to_planet_id, true, true);
		// remplir de la meme maniere qu'avec calc_pillage dans combat.php 
		// metal 50% | cristal 30% | pop 05% | tetra 15%
		$metal_max = $stockage_total * (0.50);
		$cristal_max = $stockage_total * (0.30);
		$tetra_max = $stockage_total * (0.20);
		if ($metal_max > $ressources->debris_metal)
		{
			$cristal_max += ($metal_max - $ressources->debris_metal);
			$metal_max = $ressources->debris_metal;
		}
		if ($cristal_max > $ressources->debris_cristal)
		{
			$tetra_max += $cristal_max - $ressources->debris_cristal;
			$cristal_max = $ressources->debris_cristal;
		}
		$metal_min = $metal_max * (0.60);
		$cristal_min = $cristal_max * (0.60);
		$tetra_min = $tetra_max * (0.60);

		$obj->ressources = array('metaux' => intval(mt_rand($metal_min, $metal_max)), 'cristaux' => intval(mt_rand($cristal_min, $cristal_max)),
					 'tetranium' => intval(mt_rand($tetra_min, $tetra_max)));	
		$debris_take = array('debris_cristal' => $obj->ressources['cristaux'], 'debris_metal' => $obj->ressources['metaux'],'debris_tetranium' => $obj->ressources['tetranium']);
		$this->ressources->take($him, $debris_take);
		return $obj;
	}
}
?>
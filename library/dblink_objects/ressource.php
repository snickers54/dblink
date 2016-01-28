<?php
class ressource 
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

    public function growTerrains($terrains)
    {
      $this->metaux_grow_bonus = ($this->metaux_grow * $terrains['metaux'] / 100);
      $this->cristaux_grow_bonus = ($this->cristaux_grow * $terrains['cristaux'] / 100);
      $this->tetranium_grow_bonus = ($this->tetranium_grow * $terrains['tetranium'] / 100);
    }

    public function bonusRaideur()
    {
      $this->metaux_grow += ($this->metaux_grow * BONUS_RACE / 100);     
      $this->cristaux_grow += ($this->cristaux_grow * BONUS_RACE / 100);     
      $this->tetranium_grow += ($this->tetranium_grow * BONUS_RACE / 100);     
    }

    public function updatePopulationGrowth($stats_civils)
    {
      $growth = START_POPULATION_GROW + ($stats_civils / 10);
      $this->population_grow = $growth;
    }

    public function refresh($weather)
    {
      $time = time();
      $laps_time = $time - $this->last_date;
      // s'il y a un bonus de race on l'ajout direct aux bonus de terrains, ca economise des lignes de code
        // on ajoute les modificateurs de terrains
      $this->metaux_grow += $this->metaux_grow_bonus;
      $this->cristaux_grow += $this->cristaux_grow_bonus;
      $this->tetranium_grow += $this->tetranium_grow_bonus;

      if ($this->metaux < $this->limit_metaux)
      {
        $metaux_productivity = ($this->energie < 0) ? 35 : $this->metaux_productivity;
        $new_metaux = (($laps_time * ($this->metaux_grow / HOUR)) * ($metaux_productivity / 100));
        $this->metaux = min($this->metaux + $new_metaux, $this->limit_metaux);
      }
      // si on est au dessus des limites de stockage
      else
      {
        $pourcentage = 0.10;
        if ($weather == "rainy" || $weather == "windy")
          $pourcentage += 0.05;
        if ($weather == "stormy" || $weather == "snowy")
          $pourcentage += 0.10;
        $surplus = $this->metaux - $this->limit_metaux;
        $this->metaux = $this->metaux - round($laps_time * (($surplus * $pourcentage) / HOUR));
        if ($this->metaux < $this->limit_metaux)
          $this->metaux = $this->limit_metaux;
      }

      if ($this->cristaux < $this->limit_cristaux)
      {
        $cristaux_productivity = ($this->energie < 0) ? 35 : $this->cristaux_productivity;
        $new_cristaux = (($laps_time * ($this->cristaux_grow / HOUR)) * ($cristaux_productivity / 100));
        $this->cristaux = min($this->cristaux + $new_cristaux, $this->limit_cristaux);
      }
      else
      {
        $pourcentage = 0.10;
        if ($weather == "rainy" || $weather == "windy")
          $pourcentage += 0.05;
        if ($weather == "stormy" || $weather == "snowy")
          $pourcentage += 0.10;
        $surplus = $this->cristaux - $this->limit_cristaux;
        $this->cristaux = $this->cristaux - round($laps_time * (($surplus * $pourcentage) / HOUR));
        if ($this->cristaux < $this->limit_cristaux)
          $this->cristaux = $this->limit_cristaux;
      }

      if ($this->population < $this->limit_population)
      {
        $new_population = (($laps_time * ($this->population_grow / HOUR)));
        $this->population = min($this->population + $new_population, $this->limit_population);
      }
      else
      {
        $pourcentage = 0.20;
        $surplus = $this->population - $this->limit_population;
        $this->population = $this->population - round($laps_time * (($surplus * $pourcentage) / HOUR));
        if ($this->population < $this->limit_population)
          $this->population = $this->limit_population;
      }

      if ($this->tetranium < $this->limit_tetranium)
      {
        $tetranium_productivity = ($this->energie < 0) ? 35 : $this->tetranium_productivity;
        $new_tetranium = (($laps_time * ($this->tetranium_grow / HOUR)) * ($tetranium_productivity / 100));
        $this->tetranium = min($this->tetranium + $new_tetranium, $this->limit_tetranium);
      }
      else
      {
        $pourcentage = 0.15;
        if ($weather == "rainy" || $weather == "windy")
          $pourcentage += 0.05;
        if ($weather == "stormy" || $weather == "snowy")
          $pourcentage += 0.10;
        $surplus = $this->tetranium - $this->limit_tetranium;
        $this->tetranium = $this->tetranium - round($laps_time * (($surplus * $pourcentage) / HOUR));
        if ($this->tetranium < $this->limit_tetranium)
          $this->tetranium = $this->limit_tetranium;

      }
      $this->last_date = $time;
    }

    public function getJSON()
    {
      return $this->json;
    }

}
?>
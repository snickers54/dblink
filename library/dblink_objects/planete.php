<?php
class planete 
{
	private $class = array();
  private $extensions = array();
	private $ressources;
  private $json;
  private $batiments;
  private $vaisseaux;
  private $technologie;
  private $defensif;
  private $civils;
  private $structure = array('id', 'user_id', 'nom', 'planete_type_id', 'case', 'galaxie', 'x', 'y',
                            'angle', 'weather', 'avatar', 'active', 'note', 'last_update');

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

	public function getRessources()
	{
		return $this->ressources;
	}

	public function setRessources($ressources)
	{
		$this->ressources = $ressources;
	}

  public function updatePosition()
  {
    $xs = 50;
    $ys = 50;
    $rayon = sqrt(pow($xs-$this->x, 2) + pow($ys-$this->y, 2));
    $this->x = round($xs + ($rayon * cos(deg2rad($this->angle))), 2);
    $this->y = round($ys + ($rayon * sin(deg2rad($this->angle))), 2);
  }

  public function getDistance($planet)
  {
    $planet->updatePosition();
    self::updatePosition();
    $x1 = $this->x;
    $x2 = $planet->x;
    $y1 = $this->y;
    $y2 = $planet->y;
    $inside = round(sqrt(pow($x1-$x2, 2) + pow($y1-$y2, 2)) / 3.26, 2);
    if ($this->galaxie == $planet->galaxie)
      return $inside;
    $diff_normal = abs($this->galaxie - $planet->galaxie);
    $diff_plus = abs((255 - $this->galaxie) + $planet->galaxie);
    $diff_moins = abs((255 - $planet->galaxie) + $this->galaxie);
    $diff = min($diff_normal, $diff_plus, $diff_moins);
    return $inside + ($diff * 18);
  }

  public function getTime($planet, $bonus = 0)
  {
    $distance = self::getDistance($planet);
    $base = 25 * 60;
    $time = round($base * $distance / 22);
    $time = $time - ($time * $bonus / 100);
    return $time;
  }

	public function __construct($string, $ressources, $bats)
	{
    $this->json = $string;
    $this->batiments = $bats['batiments'];
    $this->vaisseaux = $bats['vaisseaux'];
    $this->technologie = $bats['technologie'];
    $this->defensif = $bats['defensif'];
    $this->civils = $bats['civils'];

		$this->ressources = $ressources;
		$array = json_decode($string, true);
		foreach ($array AS $key => $value)
        if (in_array($key, $this->structure))
			     $this->class[$key] = $value;
        else
          $this->extensions[$key] = $value;
    self::updatePosition();
    $this->extensions['distance'] = round(sqrt(pow($this->x - 50, 2) + pow($this->y - 50, 2)) / 3.26, 2);
    $this->extensions['revolution'] = round((3600 * $this->distance / 70) / 60);
	}

  public function getBatiment() {return $this->batiments;}
  public function getTechnologie() {return $this->technologie;}
  public function getDefensif() {return $this->defensif;}
  public function getVaisseau() {return $this->vaisseaux;}
  public function getCivils() {return $this->civils;}
  public function getJSON() {return $this->json;}
}

?>
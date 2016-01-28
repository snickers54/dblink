<?php
include_once('dblink_objects/combat_specs.php');
class combat {
	// DEBUT PROLOGUE LIB
	private $class;
	private $instance = array();
	// a redefinir, peut etre en fonction du nombre d'assaillants et du nombre de defenseurs
	private $current_tour = 50;
	private $energie_defense = 0;
	private $tours = 0;

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

	// 
	public function run($obj)
	{
		$obj->active = "action";
		// defenses de la planete cible
		$defenses = $this->defenses->get($obj->to_planet_id, true);
		// faire un clone pour comparer apres le combat
		$defenses_clone = clone $defenses;

		$civils = $this->civils->get($obj->to_planet_id, true);
		$civils_clone = clone($civils);

		// vaisseaux de la planete cible
		$d_vaisseaux = $this->vaisseaux->get($obj->to_planet_id, true);
		// faire un clone pour comparer apres le combat
		$d_vaisseaux_clone = clone $d_vaisseaux;
		// vaisseaux de l'attaqunt
		$a_vaisseaux = $this->vaisseaux->create($obj->object, true);
		// faire un clone pour comparer apres le combat
		$a_vaisseaux_clone = clone $a_vaisseaux;
		// on recupere l'objet ressource de la planete
		$ressources = $this->ressources->get($obj->to_planet_id);
		$this->energie_defense = $ressources->energie;
		// boucle de jeu comme ceci : 
		while (self::combat_continue($obj->behavior_type, $a_vaisseaux, $defenses, $d_vaisseaux))
		{
			// les defenses de la planete attaquent si la planete a assez d'energie
			if ($this->energie_defense > 0)
				self::select_and_touch($defenses, $a_vaisseaux);
			// les vaisseaux attaquants ripostes
			if ($obj->behavior_type != "fight_ships")
				self::select_and_touch($a_vaisseaux, $defenses);
			// les vaisseaux de la planete aussi 
			self::select_and_touch($d_vaisseaux, $a_vaisseaux);
			
			// les vaisseau attaquants ripostes 
			if ($obj->behavior_type != "fight_defenses")
				self::select_and_touch($a_vaisseaux, $d_vaisseaux);

			// les vaisseaux attaquants touchent les batiments civils
			// sauf s'il y a encore un bouclier en place
			if (!$defenses->bouclier_plasma && !$defenses->bouclier_gravitons && $defenses->bouclier_anti_matieres)
				self::select_and_touch($a_vaisseaux, $civils);
			$this->tours++;
		}

		// rendre les vaisseaux restant a l'attaquant
		$obj->object = $a_vaisseaux->getClass();

		// pillage seulement si on a supprime toutes les forces ennemies sur le terrain
		$obj->ressources = array('metaux' => 0, 'cristaux' => 0, 'population' => 0, 'tetranium' => 0);
		if ($defenses->count() <= 0 && $d_vaisseaux->count() <= 0)
		{
			$stockage_total = $a_vaisseaux->getStockage();
			$pillage = self::calc_pillage($stockage_total, $ressources);
			$obj->ressources = $pillage;
		}
		
		// faire le necessaire si le combat est perdu par l'attaquant
		if ($a_vaisseaux->count() <= 0)
		{
			$obj->object = array();
			$obj->ressources = array();
			$obj->action = MOVE_END;
		}
		// debris en comparant avant et apres
		$debris = array('debris_metal' => 0, 'debris_cristal' => 0, 'debris_tetranium' => 0);
		$debris = self::calc_debris($a_vaisseaux, $a_vaisseaux_clone, $debris);
		$debris = self::calc_debris($d_vaisseaux, $d_vaisseaux_clone, $debris);
		// on defini que le metal en debris est recuperable a 90%
		// le cristal a 80%
		// le tetranium a 20%
		$debris['debris_metal'] *= 0.90;
		$debris['debris_cristal'] *= 0.80;
		$debris['debris_tetranium'] *= 0.20;
		// on ajoute simplement les ressources
		$this->ressources->add($ressources, $debris);

		// faire le rapport
		self::rapport($obj, $defenses, $defenses_clone, $a_vaisseaux, $a_vaisseaux_clone, $civils, $civils_clone, $d_vaisseaux, $d_vaisseaux_clone, $debris);
		$obj->active = "done";
		return $obj;
	}

	private function calc_pillage($stockage_total, $ressources)
	{
		// metal 50% | cristal 30% | pop 05% | tetra 15%
		$metal_max = $stockage_total * (0.50);
		$cristal_max = $stockage_total * (0.30);
		$tetra_max = $stockage_total * (0.15);
		$pop_max = $stockage_total * (0.05);

		if ($metal_max > $ressources->metaux)
		{
			$cristal_max += ($metal_max - $ressources->metaux);
			$metal_max = $ressources->metaux;
		}
		if ($cristal_max > $ressources->cristaux)
		{
			$tetra_max += $cristal_max - $ressources->cristaux;
			$cristal_max = $ressources->cristaux;
		}
		if ($tetra_max > $ressources->tetranium)
		{
			$pop_max += $tetra_max - $ressources->tetranium;
			$tetra_max = $ressources->tetranium;
		}
		$pop_max = ($pop_max > $ressources->population) ? $ressources->population : $pop_max;
		$metal_min = $metal_max * (0.60);
		$cristal_min = $cristal_max * (0.60);
		$pop_min = $pop_max * (0.06);
		$tetra_min = $tetra_max * (0.60);

		return array('metaux' => intval(mt_rand($metal_min, $metal_max)), 'cristaux' => intval(mt_rand($cristal_min, $cristal_max)),
					'population' => intval(mt_rand($pop_min, $pop_max)), 'tetranium' => intval(mt_rand($tetra_min, $tetra_max)));		
	}

	private function calc_debris($obj_end, $obj_start, $ressources)
	{
		$array = $obj_start->getDatas();
		if (count($array) > 0)
		foreach ($array AS $code => $def)
		{
			$start = $obj_end->$code;
			$nb_destruct = $def['number'] - ((!isset($start['number'])) ? 0 : $start['number']);
			$ressources['debris_metal'] += ($def['metaux_base'] * $nb_destruct);
			$ressources['debris_cristal'] += ($def['cristaux_base'] * $nb_destruct);
			$ressources['debris_tetranium'] += ($def['tetranium_base'] * $nb_destruct);
		}
		return $ressources;
	}

	private function select_and_touch($obj1, $obj2)
	{
		$bool = false;
		$code1 = $obj1->getRandom($this->energie_defense);
		$v1 = $obj1->$code1;
		// ici on fait des jets pour essayer de selectionner un ennemie en fonction des pourcentages et de ce qu'il y a de disponible
		for ($i = 1; $i <= $v1['attaque_nb']; $i++)
			if (($code2 = $obj2->try_selection($code1)) !== NULL)
			{
				// ici on essaye d'attaquer, ($obj1) $code1 attaque ($obj2) $code2
				$v2 = $obj2->$code2;
				if (!isset($v2['vie']))
					$v2['vie'] = $v2['defense_base'];
			    $specs = combat_specs::get($code1);
			    if (!isset($specs[$code2]))
			    	$touche = 50;
			    else
			    	$touche = $specs[$code2]['touche'];
				if (rand(1, 100) <= $touche)
				{
					$v2['vie'] -= $v1['attaque_base'];
					if ($v2['vie'] <= 0)
					{
						$v2['vie'] = $v2['defense_base'];
						$v2['number']--;
					}
				}
				$obj2->$code2 = $v2;
				if ($v2['number'] <= 0)
					$obj2->delete($code2);
				$bool = true;
			}
		if ($bool)
			$this->current_tour--;
	}

	// fonction qui verifie en fonction du comportement demande par le joueur si le combat doit s'arreter ou non
	private function combat_continue($behavior_type, $a_vaisseaux, $defenses, $d_vaisseaux)
	{
		// la flotte attaquante s'est fait decimee
		if ($a_vaisseaux->count() <= 0 || ($defenses->count() <= 0 && $d_vaisseaux->count() <= 0))
			return false;
		if ($behavior_type == "fight_death")
		{
			if ($defenses->count() <= 0 && $d_vaisseaux->count() <= 0)
				return false;
			return true;
		}
		else if ($behavior_type == "fight_ships")
		{
			if ($d_vaisseaux->count() <= 0)
				return false;
			return true;
		}
		else if ($behavior_type == "fight_defenses")
		{
			if ($defenses->count() <= 0)
				return false;
			return true;
		}
		else
		{
			if ($this->current_tour <= 0)
				return false;
			return true;
		}
	}

	// genere le rapport de combat
	public function rapport($obj, $def, $def_clone, $a_vaisseaux, $a_vaisseaux_clone, $civils, $civils_clone, $d_vaisseaux, $d_vaisseaux_clone, $debris)
	{
		// faire un rapport en json avec toutes les infos necessaires
		// enregistrer en bdd ce json + le type de deplacement ou l'id du deplacement
		// et creer une vue par type de rapport qui sera interprete .. ce uqi permettra de changer la vue sans tout refaire
		$array = array(
				'attaquant' => array(
										'vaisseaux_end' => $a_vaisseaux->getDatas(), 
										'vaisseaux_start' => $a_vaisseaux_clone->getDatas(),
										'loot' => $obj->ressources
									),
				'defenseur' => array(
										'vaisseaux_start' => $d_vaisseaux_clone->getDatas(),
										'vaisseaux_end' => $d_vaisseaux->getDatas(),
										'defenses_start' => $def_clone->getDatas(),
										'defenses_end' => $def->getDatas(),
										'civils_start' => $civils_clone->getDatas(),
										'civils_end' => $civils->getDatas(),
										'debris' => $debris
									)
			);
		$json = json_encode($array);
		$this->rapports->add($json, "combat", array('from_user_id' => $obj->from_user_id,
													'to_user_id' => $obj->to_user_id, 
													'from_planet_id' => $obj->from_planet_id, 
													'to_planet_id' => $obj->to_planet_id));
	}
}
?>
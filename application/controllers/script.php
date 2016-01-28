<?php
include_once(dirname(__FILE__).'/../../library/dblink_objects/building.php');

class scriptController extends controller
{
	public function indexAction()
	{
		$this->template->changeHeader(false);
		$this->template->changeFooter(false);
		if (isset($_GET['pass']) && md5(CRON_PASSWORD) == $_GET['pass'])
		{
			// on met a jours la bourse 
			$this->bourse->refresh();
			// ici on le rajoute a redis les nouveaux deplacements.
			$ids = $this->deplacements->checkMoves();
			// mettre a jours les deplacements
			foreach ($ids AS $id)
			{
				$obj = $this->deplacements->get($id);
				if (($obj->action == MOVE_BACK || $obj->action == MOVE_END) 
					&& $obj->active == 'waiting')
				{
					$type = $obj->type;
					$obj = $this->$type->run($obj);
					$this->deplacements->save($obj);
				}
				// pas oublier la notification de retour si jamais les vaisseaux sont encore vivants
				if ($obj->action == MOVE_END && $obj->active == "done")
					$this->deplacements->delete($obj);
			}

			$this->tokens->addRandom();
			$this->tokens->clean();
			// ici on verifie les constructions de tous les joueurs et on met ca a jours ! 
			// faudra en profiter pour mettre a jours les stats
			$ids = $this->model->getAllPlanetes();
			foreach ($ids AS $id)
				if (!$this->planetes->isCurrentlyUsed($id['id']))
					$this->batiments->refreshConstruction($id['id']);
			// calculer les stats toutes les 6h 
			$this->stats->updateStats();
			$this->espionnage->checkScan();
		}
	}

	public function showB()
	{
		$code = $_GET['code'];
		$this->template->b = $this->model->getBatiment($code);
		$this->template->setView("script_simulateur_batiment");
	}

	public function calculB()
	{
		extract($_GET);
		$building = new building();
		$array = array();
		for ($i = 1; $i <= $level_max; $i++)
		{
			$time = $building->constructionTime($factor_time, $i, 0);
			$ressources_bases = array('metaux' => $metaux_base,
                              'cristaux' => $cristaux_base,
                              'population' => $population_base,
                              'tetranium' => $tetranium_base);
			$energie = $energie_base;
			for ($j = 1; $j < $i; $j++)
				$energie += $energie * (ENERGIE_FACTOR / 100);
			$r = $building->constructionRessources($ressources_bases, $cost_augmentation, $i, 1);
			$array[] = array('level' => $i, 'time' => $time, 'metaux' => round($r['metaux']),
								'cristaux' => round($r['cristaux']), 'tetranium' => round($r['tetranium']), 'energie' => round($energie),
								'population' => round($r['population']));
		}
		$this->template->array = $array;
		$this->template->setView("script_simulateur_batiment_content");
	}

	public function simulateurAction()
	{
		$this->template->batiments = $this->model->getBatimentTechnoCodes();
		$this->template->setView("script_simulateur_ressources");
		$this->addJavascript("script");
	}
}
?>
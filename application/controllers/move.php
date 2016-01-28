<?php

class moveController extends controller
{
	public function indexAction()
	{
		$this->users->needLogin();
		$this->addJavascript("move");
		$this->template->loadLanguage("board");
		$this->template->setView("move");
		$this->template->planetes = $this->model->getAllPlanets($_SESSION['user']['user_id']);
		$this->template->input = $this->deplacements->allInputMove($_SESSION['user']['user_id']);
		$output = $this->deplacements->allOutputMove($_SESSION['user']['user_id']);
		foreach ($output AS $key => $val)
			$output[$key]['vaisseaux'] = $this->vaisseaux->create($val['object'], true)->getDatas();
		$this->template->output = $output;
	}

	public function resourceAction()
	{
		$this->users->needLogin();
		$this->template->loadLanguage("board");
		$this->template->setView("desk");
		$this->template->suffixe = "desk_resource.html";
		if (!isset($_GET['planet_id']))
			$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
		$planet_id = $_GET['planet_id'];
		if (!($planet = $this->planetes->get($planet_id)))
			$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
		if (!($user = $this->users->get($planet->user_id)))
			$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
		$planet->label_weather = $this->planetes->labelWeathers($planet->weather);    
		$this->template->planet = $planet->getDatas();
		$this->template->user = $user->getDatas();
		$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
		$this->template->vaisseaux = $vaisseau->getDatas();
		$myplanete = $this->planetes->get($_SESSION['user']['planet_id']);
		$me = $this->users->get($_SESSION['user']['user_id']);
		$this->template->distance = $myplanete->getDistance($planet, ($me->race == "explorateur") ? BONUS_RACE : 0);
		$this->template->time = $myplanete->getTime($planet);    
	}

	public function doResourceAction()
	{
		$this->users->needLogin();
		$this->template->loadLanguage("board");
		if (!isset($_POST['planet_id']))
			$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
		$planet_id = $_POST['planet_id'];
		if (!($planet = $this->planetes->get($planet_id)))
			$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
		if (!($user = $this->users->get($planet->user_id)))
			$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
		if (!isset($_POST['ship']) || count($_POST['ship']) == 0)
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/resource?planet_id=".$planet_id);
		$myplanet = $this->planetes->get($_SESSION['user']['planet_id']);
		$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
		$ressource = $this->ressources->get($_SESSION['user']['planet_id']);

		$array = $vaisseau->prepareMove($_POST['ship']);
		$ship = $array['ship'];
		if ($_POST['behavior_type'] != "resource_ships")
		{
			$r = array('tetranium' => $array['tetranium'] + $_POST['tetranium'], 'metaux' => $_POST['metal'], 'cristaux' => $_POST['cristal'], 'population' => $_POST['population']);
			if ($array['stockage'] < ($_POST['tetranium'] + $_POST['metal'] + $_POST['cristal'] + $_POST['population']))
				$this->template->redirect($this->template->language['not_enough_place'], TRUE, "/move/resource?planet_id=".$planet_id);
		}
		else
			$r = array('tetranium' => $array['tetranium']);
		$me = $this->users->get($_SESSION['user']['user_id']);
		$time = $planet->getTime($myplanet, ($me->race == "explorateur") ? BONUS_RACE : 0);
		$distance = $planet->getDistance($myplanet);
		if (count($ship) == 0)
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/resource?planet_id=".$planet_id);
		if (!$this->ressources->take($ressource, $r))
			$this->template->redirect($this->template->language['not_enough_ressources'], TRUE, "/move/resource?planet_id=".$planet_id);
		$r['tetranium'] -= $array['tetranium'];
		if (!$this->vaisseaux->take($vaisseau, $ship))
		{
			$this->ressources->add($ressource, $r);
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/resource?planet_id=".$planet_id);
		}
		$this->deplacements->add(array('to_user_id' => $planet->user_id, 'from_planet_id' => $_SESSION['user']['planet_id'],
			'to_planet_id' => $planet_id, 'from_user_id' => $_SESSION['user']['user_id'],
			'time_go' => $time, 'time_back' => $time, 'time_action' => 0, 'distance' => $distance,
			'object' => $ship, 'ressources' => $r, 'type' => 'ressources', 'behavior_type' => $_POST['behavior_type']));
		$this->users->addStats($this->users->get($_SESSION['user']['user_id']), "nb_ressource", 1);
		$this->template->redirect($this->template->language['resource_success_launch'], FALSE, "/board/galaxy");
	}

	public function recyclageAction()
	{
		$this->users->needLogin();
		$this->template->loadLanguage("board");
		$this->template->setView("desk");
		$this->template->suffixe = "desk_recyclage.html";
		if (!isset($_GET['planet_id']))
			$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
		$planet_id = $_GET['planet_id'];
		if (!($planet = $this->planetes->get($planet_id)))
			$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
		if (!($user = $this->users->get($planet->user_id)))
			$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
		$planet->label_weather = $this->planetes->labelWeathers($planet->weather);    
		$this->template->planet = $planet->getDatas();
		$this->template->user = $user->getDatas();
		$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
		$this->template->vaisseaux = $vaisseau->getDatas();
		$myplanete = $this->planetes->get($_SESSION['user']['planet_id']);
		$this->template->distance = $myplanete->getDistance($planet);
		$me = $this->users->get($_SESSION['user']['user_id']);

		$this->template->time = $myplanete->getTime($planet, ($me->race == "explorateur") ? BONUS_RACE : 0);    
	}

	public function doRecyclageAction()
	{
		$this->users->needLogin();
		$this->template->loadLanguage("board");
		if (!isset($_POST['planet_id']))
			$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
		$planet_id = $_POST['planet_id'];
		if (!($planet = $this->planetes->get($planet_id)))
			$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
		if (!($user = $this->users->get($planet->user_id)))
			$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
		if (!isset($_POST['ship']) || count($_POST['ship']) == 0)
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/recyclage?planet_id=".$planet_id);
		$myplanet = $this->planetes->get($_SESSION['user']['planet_id']);
		$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);

		$array = $vaisseau->prepareMove($_POST['ship']);
		$ship = $array['ship'];
		if ($_POST['behavior_type'] != "resource_ships")
		{
			$r = array('tetranium' => $array['tetranium'] + $_POST['tetranium'], 'metaux' => $_POST['metal'], 'cristaux' => $_POST['cristal'], 'population' => $_POST['population']);
			if ($array['stockage'] < ($_POST['tetranium'] + $_POST['metal'] + $_POST['cristal'] + $_POST['population']))
				$this->template->redirect($this->template->language['not_enough_place'], TRUE, "/move/recyclage?planet_id=".$planet_id);
		}
		else
			$r = array('tetranium' => $array['tetranium']);

		$time = $planet->getTime($myplanet);
		$distance = $planet->getDistance($myplanet);
		if (count($ship) == 0)
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/recyclage?planet_id=".$planet_id);
		$r['tetranium'] -= $array['tetranium'];
		if (!$this->vaisseaux->take($vaisseau, $ship))
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/recyclage?planet_id=".$planet_id);
		$this->deplacements->add(array('to_user_id' => $planet->user_id, 'from_planet_id' => $_SESSION['user']['planet_id'],
			'to_planet_id' => $planet_id, 'from_user_id' => $_SESSION['user']['user_id'],
			'time_go' => $time, 'time_back' => $time, 'time_action' => 0, 'distance' => $distance,
			'object' => $ship, 'ressources' => $r, 'type' => 'recyclage', 'behavior_type' => $_POST['behavior_type']));
		$this->users->addStats($this->users->get($_SESSION['user']['user_id']), "nb_recyclage", 1);
		$this->template->redirect($this->template->language['recyclage_success_launch'], FALSE, "/board/galaxy");
	}

	public function colonisationAction()
	{
		$this->users->needLogin();
		$this->template->loadLanguage("board");
		$this->template->setView("desk");
		$this->template->suffixe = "desk_colonisation.html";
		if ($this->planetes->nbPlanetsPlayer($_SESSION['user']['user_id']) >= LIMIT_PLANETE)
			$this->template->redirect($this->template->language['limit_planet_reached'], TRUE, "/board/galaxy");
		if (!isset($_GET['planet_id']))
			$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
		$planet_id = $_GET['planet_id'];
		if (!($planet = $this->planetes->get($planet_id)))
			$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
		if (($user = $this->users->get($planet->user_id)) && $planet->user_id > 0)
			$this->template->redirect($this->template->language['move_planet_habited'], TRUE, "/board/galaxy");
		$planet->label_weather = $this->planetes->labelWeathers($planet->weather);    
		$this->template->planet = $planet->getDatas();
		$this->template->user = $user->getDatas();
		$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
		$this->template->vaisseaux = $vaisseau->getDatas();
		$myplanete = $this->planetes->get($_SESSION['user']['planet_id']);
		$this->template->distance = $myplanete->getDistance($planet);
		$me = $this->users->get($_SESSION['user']['user_id']);

		$this->template->time = $myplanete->getTime($planet, ($me->race == "explorateur") ? BONUS_RACE : 0);
	}

	public function doColonisationAction()
	{
		$this->users->needLogin();
		$this->template->loadLanguage("board");
		if ($this->planetes->nbPlanetsPlayer($_SESSION['user']['user_id']) >= LIMIT_PLANETE)
			$this->template->redirect($this->template->language['limit_planet_reached'], TRUE, "/board/galaxy");
		if (!isset($_POST['planet_id']))
			$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
		$planet_id = $_POST['planet_id'];
		if (!($planet = $this->planetes->get($planet_id)))
			$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
		if (($user = $this->users->get($planet->user_id)) && $planet->user_id > 0)
			$this->template->redirect($this->template->language['move_planet_habited'], TRUE, "/board/galaxy");
		if (!isset($_POST['ship']) || count($_POST['ship']) == 0)
			$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/colonisation?planet_id=".$planet_id);
		$myplanet = $this->planetes->get($_SESSION['user']['planet_id']);
		$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
		$ressource = $this->ressources->get($_SESSION['user']['planet_id']);

		$array = $vaisseau->prepareMove($_POST['ship']);
		$ship = $array['ship'];
		$colonisateur = false;
		foreach ($ship AS $code => $val)
		{
			if ($code == "colonisateur")
				$colonisateur = true;
			if (!$colonisateur)
				$this->template->redirect($this->template->language['need_colonisateur'], TRUE, "/move/colonisation?planet_id=".$planet_id);
			$r = array('tetranium' => $array['tetranium'] + $_POST['tetranium'], 'metaux' => $_POST['metal'], 'cristaux' => $_POST['cristal'], 'population' => $_POST['population']);
			if ($array['stockage'] < ($_POST['tetranium'] + $_POST['metal'] + $_POST['cristal'] + $_POST['population']))
				$this->template->redirect($this->template->language['not_enough_place'], TRUE, "/move/resource?planet_id=".$planet_id);
			$me = $this->users->get($_SESSION['user']['user_id']);
			$time = $planet->getTime($myplanet, ($me->race == "explorateur") ? BONUS_RACE : 0);
			$distance = $planet->getDistance($myplanet);
			if (count($ship) == 0)
				$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/colonisation?planet_id=".$planet_id);
			if (!$this->ressources->take($ressource, $r))
				$this->template->redirect($this->template->language['not_enough_ressources'], TRUE, "/move/colonisation?planet_id=".$planet_id);
			if (!$this->vaisseaux->take($vaisseau, $ship))
			{
				$this->ressources->add($ressource, $r);
				$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/colonisation?planet_id=".$planet_id);
			}
			$this->deplacements->add(array('to_user_id' => $planet->user_id, 'from_planet_id' => $_SESSION['user']['planet_id'],
				'to_planet_id' => $planet_id, 'from_user_id' => $_SESSION['user']['user_id'],
				'time_go' => $time, 'time_back' => $time, 'time_action' => ($_POST['behavior_type'] == 'colonisation_normal') ? 8 * HOUR: 14 * HOUR, 'distance' => $distance,
				'object' => $ship, 'ressources' => array(), 'type' => 'colonisation', 'behavior_type' => $_POST['behavior_type']));
			$this->colonisation->booked($planet_id, $_SESSION['user']['user_id']);
			$this->template->redirect($this->template->language['colonisation_success_launch'], FALSE, "/board/galaxy");
		}
	}
		public function launchScan()
		{
			$this->users->needLogin();
			$this->template->loadLanguage("board");
			$this->template->time_scan = $this->espionnage->calculScanTime($_SESSION['user']['planet_id']);
			$this->espionnage->launchScan($_SESSION['user']['planet_id']);
			$this->template->redirect($this->template->language['spy_scan_launched_success'], false, "");
		}

		public function spyAction()
		{
			$this->users->needLogin();
			$this->template->loadLanguage("board");
			$this->template->setView("desk");    
			$this->template->suffixe = "desk_espionnage.html";
			if (!isset($_GET['planet_id']))
				$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
			$planet_id = $_GET['planet_id'];
			if (!($planet = $this->planetes->get($planet_id)))
				$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
// verifier qu'on attaque pas une planete de notre alliance 
			$myuser = $this->users->get($_SESSION['user']['user_id']);
			if ($this->espionnage->isScanning($_SESSION['user']['planet_id']))
				$this->template->isScanning = true;
			if (!($user = $this->users->get($planet->user_id)))
				$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
			if ($user->user_id == $_SESSION['user']['user_id'])
				$this->template->redirect($this->template->language['spy_yourself'], TRUE, "/board/galaxy");
// ne pas oublier d'ajouter la protection des joueurs faibles
			$planet->label_weather = $this->planetes->labelWeathers($planet->weather);    
			$this->template->planet = $planet->getDatas();
			$this->template->user = $user->getDatas();
			$this->template->chance = $this->espionnage->calculChance($_SESSION['user']['planet_id'], $planet_id);
			if (!$this->espionnage->getSpyActive($_SESSION['user']['planet_id'], $planet_id))
			{
				$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
				$this->template->vaisseaux = $vaisseau->getDatas();
			}
			$myplanete = $this->planetes->get($_SESSION['user']['planet_id']);
			$this->template->distance = $myplanete->getDistance($planet);
			$me = $this->users->get($_SESSION['user']['user_id']);
			$this->template->time = $myplanete->getTime($planet, ($me->race == "explorateur") ? BONUS_RACE : 0);
		}

		public function doSpyAction()
		{
			$this->users->needLogin();
			$this->template->loadLanguage("board");
			if (!isset($_POST['planet_id']))
				$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
			$planet_id = $_POST['planet_id'];
			if (!($planet = $this->planetes->get($planet_id)))
				$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
			if (!($user = $this->users->get($planet->user_id)))
				$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
			if ($user->user_id == $_SESSION['user']['user_id'])
				$this->template->redirect($this->template->language['spy_yourself'], TRUE, "/board/galaxy");
			if (!$this->espionnage->isScanning($_SESSION['user']['planet_id']))
				$this->template->redirect($this->template->language['spy_scan_running'], TRUE, "/move/spy?planet_id=".$planet_id);
			$this->users->addStats($this->users->get($_SESSION['user']['user_id']), "nb_espionnage", 1);
			if (!$this->espionnage->getSpyActive($_SESSION['user']['planet_id'], $planet_id))
			{
				if (!isset($_POST['ship']) || count($_POST['ship']) == 0)
					$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/spy?planet_id=".$planet_id);
    // lancer la sonde vers la planete via un deplacement
				$myplanet = $this->planetes->get($_SESSION['user']['planet_id']);
				$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
				$ressource = $this->ressources->get($_SESSION['user']['planet_id']);
				$array = $vaisseau->prepareMove($_POST['ship']);
				$ship = $array['ship'];
				$tetranium = $array['tetranium'];
				$me = $this->users->get($_SESSION['user']['user_id']);
				$time = $planet->getTime($myplanet, ($me->race == "explorateur") ? BONUS_RACE : 0);
				$distance = $planet->getDistance($myplanet);
				if (count($ship) == 0)
					$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/spy?planet_id=".$planet_id);
				if (!$this->ressources->take($ressource, array('tetranium' => $tetranium)))
					$this->template->redirect($this->template->language['not_enough_ressources'], TRUE, "/move/spy?planet_id=".$planet_id);
				if (!$this->vaisseaux->take($vaisseau, $ship))
				{
					$this->ressources->add($ressource, array('tetranium' => $tetranium));
					$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/spy?planet_id=".$planet_id);
				}
				$this->deplacements->add(array('to_user_id' => $planet->user_id, 'from_planet_id' => $_SESSION['user']['planet_id'],
					'to_planet_id' => $planet_id, 'from_user_id' => $_SESSION['user']['user_id'],
					'time_go' => $time, 'time_back' => $time, 'time_action' => 0, 'distance' => $distance,
					'object' => $ship, 'ressources' => array(), 'type' => 'espionnage', 'behavior_type' => $_POST['behavior_type']));
				$this->template->redirect($this->template->language['spy_success_launch'], FALSE, "/move/spy?planet_id=".$planet_id);
			}
			else
			{
				$chance_prox = $this->espionnage->calculChance($_SESSION['user']['planet_id'], $planet_id);
				$chance = rand(0, 100);
				if ($chance <= $chance_prox)
				{
					$json = $this->espionnage->rapport($planet_id);
					$this->rapports->add($json, "espionnage", array('from_user_id' => $_SESSION['user']['user_id'],
						'to_user_id' => $planet->user_id, 
						'from_planet_id' => $_SESSION['user']['planet_id'], 
						'to_planet_id' => $planet_id));
					$this->users->addStats($this->users->get($_SESSION['user']['user_id']), "espionnage_success", 1);
				}
				else
				{
					$json = json_encode(array('failed' => 'true'));
					$this->rapports->add($json, "espionnage", array('from_user_id' => $_SESSION['user']['user_id'],
						'to_user_id' => $planet->user_id, 
						'from_planet_id' => $_SESSION['user']['planet_id'], 
						'to_planet_id' => $planet_id));
					$json = json_encode(array('findout' => 1));
					$this->rapports->add($json, "espionnage", array('to_user_id' => $_SESSION['user']['user_id'],
						'from_user_id' => $planet->user_id, 
						'to_planet_id' => $_SESSION['user']['planet_id'], 
						'from_planet_id' => $planet_id));
					$this->users->addStats($this->users->get($_SESSION['user']['user_id']), "espionnage_fail", 1);
					$this->users->addStats($user, "espionnage_avoid", 1);
				}
			}
		}

		public function doAttackAction()
		{
			$this->users->needLogin();
			$this->template->loadLanguage("board");
			if (!isset($_POST['planet_id']))
				$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
			$planet_id = $_POST['planet_id'];
			if (!($planet = $this->planetes->get($planet_id)))
				$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
// pouvoir attaquer des planetes controles par des bots
// verifier que ce n'est pas un joueurs faible ...
			if (!($user = $this->users->get($planet->user_id)))
				$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
			if ($user->user_id == $_SESSION['user']['user_id'])
				$this->template->redirect($this->template->language['attack_yourself'], TRUE, "/board/galaxy");
			if (!isset($_POST['ship']) || count($_POST['ship']) == 0)
				$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/attack?planet_id=".$planet_id);

			$myplanet = $this->planetes->get($_SESSION['user']['planet_id']);
			$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
			$ressource = $this->ressources->get($_SESSION['user']['planet_id']);

			$array = $vaisseau->prepareMove($_POST['ship']);
			$ship = $array['ship'];
			$stockage = $array['stockage'];
			$tetranium = $array['tetranium'];
			$me = $this->users->get($_SESSION['user']['user_id']);
			$time = $planet->getTime($myplanet, ($me->race == "explorateur") ? BONUS_RACE : 0);
			$distance = $planet->getDistance($myplanet);
			if (count($ship) == 0)
				$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/attack?planet_id=".$planet_id);
			if (!$this->ressources->take($ressource, array('tetranium' => $tetranium)))
				$this->template->redirect($this->template->language['not_enough_ressources'], TRUE, "/move/attack?planet_id=".$planet_id);
			if (!$this->vaisseaux->take($vaisseau, $ship))
			{
				$this->ressources->add($ressource, array('tetranium' => $tetranium));
				$this->template->redirect($this->template->language['move_no_ship'], TRUE, "/move/attack?planet_id=".$planet_id);
			}
			$this->deplacements->add(array('to_user_id' => $planet->user_id, 'from_planet_id' => $_SESSION['user']['planet_id'],
				'to_planet_id' => $planet_id, 'from_user_id' => $_SESSION['user']['user_id'],
				'time_go' => $time, 'time_back' => $time, 'time_action' => 0, 'distance' => $distance,
				'object' => $ship, 'ressources' => array(), 'type' => 'combat', 'behavior_type' => $_POST['behavior_type']));
			$this->users->addStats($this->users->get($_SESSION['user']['user_id']), "nb_attack", 1);
			$this->template->redirect($this->template->language['attack_success'], FALSE, "/move/attack?planet_id=".$planet_id);
		}

		public function attackAction()
		{
			$this->users->needLogin();
			$this->template->loadLanguage("board");
			$this->template->setView("desk");

			$this->template->suffixe = "desk_attack.html";
			if (!isset($_GET['planet_id']))
				$this->template->redirect($this->template->language['move_no_planet'], TRUE, "/board/galaxy");
			$planet_id = $_GET['planet_id'];
			if (!($planet = $this->planetes->get($planet_id)))
				$this->template->redirect($this->template->language['move_planet_notexist'], TRUE, "/board/galaxy");
// verifier qu'on attaque pas une planete de notre alliance 
			$myuser = $this->users->get($_SESSION['user']['user_id']);

			if (!($user = $this->users->get($planet->user_id)))
				$this->template->redirect($this->template->language['move_planet_nothabited'], TRUE, "/board/galaxy");
			if ($user->user_id == $_SESSION['user']['user_id'])
				$this->template->redirect($this->template->language['attack_yourself'], TRUE, "/board/galaxy");
// ne pas oublier d'ajouter la protection des joueurs faibles
			$planet->label_weather = $this->planetes->labelWeathers($planet->weather);    
			$this->template->planet = $planet->getDatas();
			$this->template->user = $user->getDatas();
			$vaisseau = $this->vaisseaux->get($_SESSION['user']['planet_id']);
			$this->template->vaisseaux = $vaisseau->getDatas();
			if (($alliance = $user->getAlliance()))
				$this->template->alliance = $alliance->getDatas();
			$myplanete = $this->planetes->get($_SESSION['user']['planet_id']);
			$this->template->distance = $myplanete->getDistance($planet);
			$me = $this->users->get($_SESSION['user']['user_id']);
			$this->template->time = $myplanete->getTime($planet, ($me->race == "explorateur") ? BONUS_RACE : 0) * 2;
		}

}

		?>
<?php

class boardController extends controller
{
  public function indexAction()
  {
    $this->users->needLogin();
  	$this->template->setView("profil");
    $planet_id = $_SESSION['user']['planet_id'];
    $batiment = $this->batiments->get($planet_id);

  }

  public function rapportsAction()
  {
    $this->users->needLogin();
    $this->template->setView("rapports");
    $this->template->loadLanguage("board");
    $this->template->rapports = $this->rapports->getPlanet($_SESSION['user']['planet_id']);
  }

    public function spycenterAction()
    {
      $this->users->needLogin();
      $this->template->loadLanguage("board");
      $this->template->setView("spycenter");
      $spy = $this->espionnage->getSpyActive($_SESSION['user']['planet_id']);
      if (($res = $this->espionnage->isScanning($_SESSION['user']['planet_id'])))
      {
        $this->addJavascript("spy");
        $this->template->scan = json_decode($res, true);
      }
      $this->template->time_scan = $this->espionnage->calculScanTime($_SESSION['user']['planet_id']);
      if ($spy)
        foreach ($spy AS $key => $s)
          $spy[$key]['chance'] = $this->espionnage->calculChance($s['from_planet_id'], $s['to_planet_id']);
        $this->template->spy = $spy;
    }

  public function getReportEmpire()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    if (!($report = $this->model->getBatimentsRapport(intval($_GET['id']))))
      $this->template->redirect($this->template->language['rapport_not_exist'], TRUE, "");
    $this->template->addJSON(array('rapport' => $this->rapports->generateHTML($report, $report['template'])));
  }

  public function delRapport()
  {
    $this->users->needLogin();
    $id_rapport = intval($_POST['id_rapport']);
    $this->rapports->delete($id_rapport);
  }

  public function getRapport()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("rapport");
    $this->template->loadLanguage("board");
    $id = intval($_GET['id']);
    if (!($rapport = $this->rapports->get($id)))
      $this->template->redirect($this->template->language['rapport_not_exist'], TRUE, "");
    $this->template->addJSON(array('rapport' => $this->rapports->generateHTML($rapport, $rapport['template'])));
  }

  public function missionsAction()
  {
    $this->users->needLogin();
    $this->template->setView("missions");
    $this->template->loadLanguage("board");
    $user = $this->users->get($_SESSION['user']['user_id']);
    $this->template->missions = $this->missions->get($user);
    $generate = captchme_generate_html(CAPTCHME_PUBLIC);
    $this->template->captchme_generate = $this->template->language['captchme_already_voted'];
    $time = $this->users->getValueConfig($user, 'vote_captchme');
    $this->template->diff = $time - time();
    $this->template->diff_p = 100 - (($time - time()) * 100 / (2 * HOUR));
    if (!$time || ($time - time()) <= 0)
      $this->template->captchme_generate = $generate;
    if (isset($_POST["captchme_response_field"])) {
      $remoteIp = $_SERVER['REMOTE_ADDR'];
      $resp = captchme_verify(CAPTCHME_PRIVATE,
        $_POST["captchme_challenge_field"],
        $_POST["captchme_response_field"],
        $remoteIp,
        CAPTCHME_AUTH);
      if (!$resp->is_valid)
        $this->template->redirect($this->template->language['check_captcha_fail'], TRUE, "SELF");
      if ($resp->is_valid && ($time === FALSE || ($time - time()) <= 0))
      {
       $this->users->addDBGolds($user, 1);
       $this->users->setValueConfig($user, "vote_captchme", time() + (2 * HOUR));
      }
    }
  }

  public function galaxyAction()
  {
    $this->users->needLogin();
    $this->addJavascript("d3.v2.min");
    $this->addJavascript("map");
    $this->template->loadLanguage("board");
    $this->template->setView("galaxy");
    $list_galaxie = $this->model->distinctGalaxies();
    $user = $this->users->get($_SESSION['user']['user_id']);
    $planet = $user->getPlanet();
    $this->template->current_galaxie = $planet->galaxie;
    $labo = $this->template->_batiments;
    $labo = $labo['optique'];
    // le range du systeme solaire est 0 - 255
    if (isset($_GET['galaxy']))
      $this->template->current_galaxie = intval($_GET['galaxy']);
    $range_galaxie = (!isset($labo)) ? 6 : (($labo['level'] * 6) + 6);
    $array = array();
    for ($i = 0; $i < $range_galaxie; $i++)
    {
      $key = intval($this->template->current_galaxie + $i);
      $key = ($key > 255) ? $key - 255 : $key;
      $array[$key] = "";
    }
    for ($i = 0; $i < $range_galaxie; $i++)
    {
      $key = intval($this->template->current_galaxie - $i);
      $key = ($key < 0) ? $key + 255 : $key;
      $array[$key] = "";
    }
    ksort($array);
    foreach ($list_galaxie AS $key => $lg)
      if (!array_key_exists($lg['galaxie'], $array))
        unset($list_galaxie[$key]);
    $this->template->list_galaxie = $list_galaxie;
  }

  
  public function previewPlanet()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $this->template->setView("previewUser");
    $this->template->setView("previewPlanet");
    $planet_id = $_GET['id'];

    if (!($planet = $this->planetes->get($planet_id)))
      $this->template->redirect($this->template->language['planet_incorrect'], TRUE, "");

    if (($user = $this->users->get($planet->user_id)) && $planet->user_id > 0)
    {
      $this->template->user = $user->getDatas();
      $this->template->already_friends = $user->isFriend($_SESSION['user']['user_id']);
      $this->template->status = $this->planetes->getStatus($planet, $this->planetes->get($_SESSION['user']['planet_id']));
    }
    $planet->label_weather = $this->planetes->labelWeathers($planet->weather);
    $this->template->r = $planet->getRessources()->getDatas();
    $this->template->planet = $planet->getDatas();
    if ($user && $user->getAlliance())
      $this->template->alliance = $user->getAlliance()->getDatas();
  }

  public function datasUniverse()
  {
    $this->users->needLogin();
    $planetes = $this->model->getAll();
    $galaxies = $this->model->distinctGalaxies();

    $univers = new stdClass;
    $univers->name = "Univers";
    foreach ($galaxies AS $g)
    {
      $galaxie = $g['galaxie'];
      $children = new stdClass;
      $children->name = "G".$galaxie;
      foreach ($planetes AS $key => $p)
        if ($p['galaxie'] == $galaxie)
        {
          $obj = new stdClass;
          $obj->name = (strlen($p['nom']) > 0) ? $p['nom'] : "G".$galaxie."P".$p['id'];
          $obj->size = 500;
          $children->children[] = $obj;
          unset($planetes[$key]);
        }
      $univers->children[] = $children;
    }
    $this->template->addJSON(array("univers" => $univers));
  }

  public function datas()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $width = $_GET['width'];
    $height = $_GET['height'];
    $galaxie = $_GET['galaxie'];
    $labo = $this->template->_batiments;
    $labo = $labo['optique'];
    $planet1 = $this->planetes->get($_SESSION['user']['planet_id']);
    $range_galaxie = (!isset($labo)) ? 5 : (($labo['level'] * 5) + 5);
    if ($galaxie < ($planet1->galaxie - $range_galaxie) || $galaxie > ($range_galaxie + $planet1->galaxie))
      $this->template->redirect($this->template->language['not_enough_rights'], TRUE, "");
    $planet = $this->model->get($galaxie);
    if (!$planet || count($planet) <= 0)
      $this->template->redirect($this->template->language['no_planets'], TRUE, "");
    $array = array('links' => array());
    
    // ici on ajoute un soleil en coordoonnees milieu / milieu
    $soleil = new stdClass;
    $soleil->label = 'G'.$galaxie;
    $soleil->rayon = 30;
    $soleil->x = $width * 50 / 100;
    $soleil->y = $height * 50 / 100;
    $soleil->distance_soleil = 0;
    $soleil->teta = 0;
    $soleil->last_update = time();
    $soleil->fixed = true;
    $array['nodes'][] = $soleil;
    
    foreach ($planet AS $p)
    {
      $obj = new stdClass;
      $obj->label = (strlen($p['nom']) > 0) ? $p['nom'] : "G".$galaxie."P".$p['id'];
        // #friends vert | #alliance vert | #hostile rouge 
      
      $planet2 = $this->planetes->get($p['id']);
      $obj->status = ($p['user_id'] < 1) ? "vide" : $this->planetes->getStatus($planet1, $planet2);
      unset($planet2);
      $obj->rayon = intval($p['case'] * 30 / 500);
      $obj->x = intval($width * $p['x'] / 100);
      $obj->y = intval($height * $p['y'] / 100);
      $distance = sqrt(pow($obj->x - $soleil->x, 2) + pow($obj->y - $soleil->y, 2));

      $obj->last_update = $p['last_update'];
      $obj->teta = $p['angle'];
      $obj->fixed = true;
      $obj->user_id = $p['user_id'];
      $obj->planet_id = $p['id'];
      $obj->distance_soleil = $distance;
      $array['nodes'][] = $obj;
    }

    for ($current = 1; $current < count($array['nodes']); $current++)
    {
        $link = new stdClass;
        $link->source = 0;
        $link->target = $current;
        $obj = $array['nodes'][$current];
        $link->distance = $array['nodes'][$current]->distance_soleil;
        $array['links'][] = $link;
    }
    $this->template->addJSON($array);
  }

  public function createReportEmpire()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    //generate the report in json
    if (isset($_GET['vaisseaux']))
      $array['vaisseaux'] = $this->vaisseaux->get($_SESSION['user']['planet_id'], true, true)->getDatas();
    if (isset($_GET['defenses']))
      $array['defenses'] = $this->defenses->get($_SESSION['user']['planet_id'], true, true)->getDatas();
    if (isset($_GET['civils']))
      $array['civils'] = $this->civils->get($_SESSION['user']['planet_id'], true, true)->getDatas();
    $report_json = json_encode($array);
    $id = $this->model->createBatimentRapport($_SESSION['user']['user_id'], $_SESSION['user']['planet_id'], $report_json, "empire");
    $this->template->addJSON(array('id' => $id));
  }


  public function empireAction()
  {
    $this->users->needLogin();
    $this->addJavascript("board");
    $this->template->loadLanguage("board");
    $this->template->setView("empire");
    $this->template->vaisseaux = $this->vaisseaux->get($_SESSION['user']['planet_id'])->getDatas();
    $this->template->defenses = $this->defenses->get($_SESSION['user']['planet_id'])->getDatas();
    $this->template->civils = $this->civils->get($_SESSION['user']['planet_id'])->getDatas();
    if (($report = $this->model->getLastBatimentsRapport($_SESSION['user']['planet_id'])))
      $this->template->report = "[empire=".$report['id']."]";
  }

  public function constructionsAction()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $this->addJavascript("constructions");
    $this->template->setView("constructions");
    $planet = $this->planetes->get($_SESSION['user']['planet_id']);
   
    $this->template->batiments = self::calculeCurrentPrice($planet->getBatiment(), self::searchRequirement($planet->getBatiment(), $planet->getBatiment()->getDatas()));
    $this->template->defenses = self::searchRequirement($planet->getDefensif(), $planet->getDefensif()->getDatas());
    $this->template->technologies = self::calculeCurrentPrice($planet->getTechnologie(), self::searchRequirement($planet->getTechnologie(), $planet->getTechnologie()->getDatas()));
    $this->template->vaisseaux = self::searchRequirement($planet->getVaisseau(), $planet->getVaisseau()->getDatas());
    $this->template->civils = self::searchRequirement($planet->getCivils(), $planet->getCivils()->getDatas());
  }

  public function searchRequirement($obj, $datas)
  {
    $user = $this->users->get($obj->user_id);
    foreach ($datas AS $key => $d)
    {
      $datas[$key]['dependencies'] = $this->batiments->checkRequirement($_SESSION['user']['planet_id'], $key);
      $datas[$key]['construction_time'] = intval($obj->constructionTime($d['factor_time'], ((!isset($d['level'])) ? 1 : $d['level'] + 1), ($user->race == "raideur") ? BONUS_RACE : 0));
      
      $rc = $d['ressources_change'];
      $d['level'] = (!isset($d['level'])) ? 0 : $d['level'] + 1;
      $d['number'] = (!isset($d['number'])) ? 0 : $d['number'] + 1;
      $energie_temp = $d['energie_base'];
      if ($d['level'] > 0)
        for ($i = 1; $i <= $d['level']; $i++)
          $energie_temp += $energie_temp * (ENERGIE_FACTOR / 100);
      $datas[$key]['energie_current'] = round($energie_temp);
      if ($rc == "m")
        $datas[$key]['metaux_grow_produced'] = round($obj->calculeGrowth(START_METAUX_GROW, 1 + (METAUX_GROW / 100), $d['level']));
      if ($rc == "c")
        $datas[$key]['cristaux_grow_produced'] = round($obj->calculeGrowth(START_CRISTAUX_GROW, 1 + (CRISTAUX_GROW / 100), $d['level']));
      if ($rc == "t")
        $datas[$key]['tetranium_grow_produced'] = round($obj->calculeGrowth(START_TETRANIUM_GROW, 1 + (TETRANIUM_GROW / 100), $d['level']));

      if (preg_match("#^e:[0-9]{1,}$#", $rc))
      {
        $array = explode(":", $rc);
        $val = intval($array[1]);
        $datas[$key]['energie_produced'] = $val;
      }
      if (preg_match("#^lp:[0-9]{1,}$#", $rc))
      {
        $array = explode(":", $rc);
        $val = intval($array[1]);
        $datas[$key]['limit_population_produced'] = round($val);
      }
      if (preg_match("#^lm:[0-9]{1,}$#", $rc))
      {
        $array = explode(":", $rc);
        $val = intval($array[1]);
        $datas[$key]['limit_metaux_produced'] = round($val);
      }
      if (preg_match("#^lc:[0-9]{1,}$#", $rc))
      {
        $array = explode(":", $rc);
        $val = intval($array[1]);
        $datas[$key]['limit_cristaux_produced'] = round($val);
      }
      if (preg_match("#^lt:[0-9]{1,}$#", $rc))
      {
        $array = explode(":", $rc);
        $val = intval($array[1]);
        $datas[$key]['limit_tetranium_produced'] = round($val);
      }
    }
    return $datas;
  }

  public function calculeCurrentPrice($obj, $datas)
  {
    foreach ($datas AS $key => $d)
    {
      $ressources_bases = array('metaux' => $d['metaux_base'],
                              'cristaux' => $d['cristaux_base'],
                              'population' => $d['population_base'],
                              'tetranium' => $d['tetranium_base']);
      if (!isset($d['level']))
        $d['level'] = 0;
      $ressources = $obj->constructionRessources($ressources_bases, $d['cost_augmentation'], $d['level'] + 1, 1);
      $datas[$key]['metaux_current'] = round($ressources['metaux']);
      $datas[$key]['cristaux_current'] = round($ressources['cristaux']);
      $datas[$key]['population_current'] = round($ressources['population']);
      $datas[$key]['tetranium_current'] = round($ressources['tetranium']);

    }
    return $datas;
  }

  public function cancel()
  {
    $this->users->needLogin();
    $code = $this->POST['code'];

    $this->template->loadLanguage("board");

    $planet_id = $_SESSION['user']['planet_id'];
    $batiment = $this->batiments->get($planet_id);
    $vaisseau = $this->vaisseaux->get($planet_id);
    $technologie = $this->technologies->get($planet_id);
    $defense = $this->defenses->get($planet_id);
    $civil = $this->civils->get($planet_id);  

    if (($obj = $vaisseau) && !$vaisseau->$code)
      if (($obj = $batiment) && !$batiment->$code)
        if (($obj = $defense) && !$defense->$code)
          if (($obj = $technologie) && !$technologie->$code)
            if (($obj = $civil) && !$civil->$code)
              $this->template->redirect($this->template->language['construct_bad_type'], TRUE, "");
    $bat = $obj->$code;
    $ressources_bases = array('metaux' => $bat['metaux_base'],
                              'cristaux' => $bat['cristaux_base'],
                              'population' => $bat['population_base'],
                              'tetranium' => $bat['tetranium_base']);
    $level = $batiment->deleteConstruction($code);
    $nb = $vaisseau->deleteConstruction($code);
    $level += $technologie->deleteConstruction($code);
    $nb += $defense->deleteConstruction($code);
    $nb += $civil->deleteConstruction($code);

    $ressources = $obj->constructionRessources($ressources_bases, $bat['cost_augmentation'], $level, (($nb <= 0) ? 1 : $nb), 20);

    $obj_ressources = $this->ressources->get($planet_id);
    $this->ressources->add($obj_ressources, $ressources);
    $type = $obj->_type;
    $this->$type->save($obj);
    $this->template->redirect($this->template->language['construct_cancel_success'], FALSE, "");
  }

  public function currentConstruct()
  {
    $this->users->needLogin();
    $planet_id = $_SESSION['user']['planet_id'];
    $batiment = $this->batiments->get($planet_id);
    $vaisseau = $this->vaisseaux->get($planet_id);
    $technologie = $this->technologies->get($planet_id);
    $defense = $this->defenses->get($planet_id);
    $civil = $this->civils->get($planet_id);

    $this->template->addJSON(array_merge($batiment->getConstruction(), $vaisseau->getConstruction(), $technologie->getConstruction(), $defense->getConstruction(), $civil->getConstruction()));
  }

  public function construct(){
    $this->users->needLogin();
    $this->template->loadLanguage("board");

    $code = $_POST['code'];
    $number = NULL;
    $planet_id = $_SESSION['user']['planet_id'];
    if (isset($_POST['number']))
      $number = intval($_POST['number']);
    if ($number === 0)
      $this->template->redirect($this->template->language['construct_number_incorrect'], TRUE, "");
    $exist = true;
    if (!isset($batiment['number']) || !isset($batiment['level']))
      $exist = false;
    if (isset($_POST['number']))
    {
      // on cherche a recuperer l'obj
      if (($obj = $this->vaisseaux->get($planet_id)) && !$obj->$code)
        if (($obj = $this->defenses->get($planet_id)) && !$obj->$code)
          if (($obj = $this->civils->get($planet_id)) && !$obj->$code)
            $this->template->redirect($this->template->language['construct_not_found'], TRUE, "");
      $batiment = $obj->$code;
      // on verifier qu'on a pas atteint les limites de constructions
      $batiment['number'] = (!isset($batiment['number'])) ? 0 : $batiment['number'];
      if (($batiment['number'] + $number) > $batiment['level_max'] && $batiment['level_max'] > 0)
        $this->template->redirect($this->template->language['construct_number_max_reach'], TRUE, "");
    }
    else
    {
      // on cherche a recuperer l'obj
      if (($obj = $this->batiments->get($planet_id)) && !$obj->$code)
        if (($obj = $this->technologies->get($planet_id)) && !$obj->$code)
          $this->template->redirect($this->template->language['construct_not_found'], TRUE, "");          
      $batiment = $obj->$code;
      // on verifier qu'on a pas atteint les limites de constructions
      $batiment['level'] = (!isset($batiment['level'])) ? 0 : $batiment['level'];
      if ($batiment['level'] >= $batiment['level_max'] && $batiment['level_max'] > 0)
        $this->template->redirect($this->template->language['construct_level_max_reach'], TRUE, "");
      // on verifier qu'on a pas deja une construction en cours pour cette instance
      if (isset($batiment['construction']) && $batiment['construction'] !== NULL)
        $this->template->redirect($this->template->language['construct_already_in'], TRUE, "");
    }
    // on check les requirements
    if ($this->batiments->checkRequirement($planet_id, $code) !== FALSE)
      $this->template->redirect($this->template->language['construct_need_requirement'], TRUE, "");
    // peut etre des buffs qui reduisent les couts ?
    $ressources_bases = array('metaux' => $batiment['metaux_base'],
                              'cristaux' => $batiment['cristaux_base'],
                              'population' => $batiment['population_base'],
                              'tetranium' => $batiment['tetranium_base']);
    $ressources = $obj->constructionRessources($ressources_bases, $batiment['cost_augmentation'],
                                                ((!isset($batiment['level'])) ? 0 : $batiment['level']) + 1,
                                                (($number === NULL) ? 1 : $number));
    if (!$this->ressources->take($this->ressources->get($planet_id), $ressources))
      $this->template->redirect($this->template->language['construct_not_enough_ressources'], TRUE, "");
    $user = $this->users->get($_SESSION['user']['user_id']);
    // on calcule le temps
    $time = intval($obj->constructionTime($batiment['factor_time'], ((!isset($batiment['level'])) ? 1 : $batiment['level'] + 1), ($user->race == "raideur") ? BONUS_RACE : 0));
    $total_time = $time * (($number === NULL) ? 1 : $number);
    // ici on ajoute / update dans l'objet la construction
    if (isset($batiment['construction']) && $batiment['construction'] !== NULL)
    {
      $batiment['construction']['number'] += $number;
      $batiment['construction']['time'] += $total_time;
      $batiment['construction']['time_base'] = $time;
      $batiment['construction']['end'] += $total_time;
    }
    else
    {
      $batiment['construction']['number'] = (($number === NULL) ? 1 : $number);
      $batiment['construction']['time'] = $total_time;
      $batiment['construction']['time_base'] = $time;
      $batiment['construction']['start'] = time();
      $batiment['construction']['end'] = $batiment['construction']['start'] + $total_time;
    }
    $type = $obj->_type;
    if (!$exist)
    {
      if ($type == "batiments" || $type == "technologies")
        $ext = array('level' => 0);
      else
        $ext = array('attaque' => $batiment['attaque_base'], 'number' => 0);
      // on force l'ajout dans le json du nouveau truc qu'on veut creer pour etre sur qu'il existe apres
      $obj->addBuilding($code, array_merge(array('vie' => $batiment['defense_base']), $ext));
    }
    $obj->$code = $batiment;
    // on sauvegarde dans redis / bdd les changements
    $this->$type->save($obj);

    // on formate le retour en json
    if ($number)
      $this->template->addJSON(array('number' => $batiment['construction']['number']));
    else
      $this->template->addJSON(array('level' => $batiment['level'] + 1));
    $this->template->addJSON(array('nom' => $batiment['nom'], 
                                    'time' => $batiment['construction']['time'] - (time() - $batiment['construction']['start']),
                                   'totaltime' => $batiment['construction']['end'] - $batiment['construction']['start']));
    $this->template->redirect($this->template->language['construct_successfull'], FALSE, "");
  }

  public function changeProductivity()
  {
    $this->users->needLogin();
    $planet_id = $_SESSION['user']['planet_id'];
    $ressource = $this->ressources->get($planet_id);
    $prod = intval($_POST['prod']);
    $type = $_POST['type'];
    $ressource->$type = $prod;
    $this->ressources->save($ressource);
    $batiment = $this->batiments->get($planet_id, true);
    $technologie = $this->technologies->get($planet_id, true);
    $defense = $this->defenses->get($planet_id, false, true);
    $civil = $this->civils->get($planet_id, true);

    $this->batiments->updateDatas(array($batiment, $technologie, $civil, $defense), $ressource);
  }

}
?>

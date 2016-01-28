<?php

class bourseController extends controller
{

  public function indexAction()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $batiments = $this->batiments->get($_SESSION['user']['planet_id'])->getDatas();
    $batiments = $batiments['chambre_commerce'];
    if (!isset($batiments)
        || $batiments['level'] < 1)
      $this->template->redirect($this->template->language['not_enough_rights'], TRUE, "/index/index");
    $this->template->setView("bourse");
    $this->addJavascript("highcharts/highcharts");
    $this->addJavascript("bourse");
    $user = $this->users->get($_SESSION['user']['user_id']);
    $this->success->add($user, "premier_pas", 3);
    $this->template->actions = $this->bourse->getActionsFromUser($user);
  }

  public function getLast()
  {
    $this->users->needLogin();
    if (!isset($_GET['last']))
    {
      $indice = $this->bourse->get();
      $time = $indice[0]['timestamp'];
      $this->template->addJSON(array("indices" => $indice));
      $_SESSION['last_bourse'] = $time;
    }
    else
    {
      $indice = $this->bourse->getLast((isset($_GET['code'])) ? $_GET['code'] : NULL);
      if ($indice && $indice['timestamp'] > $_SESSION['last_bourse'])
      {
        $this->template->addJSON(array("indices" => array($indice)));
        $_SESSION['last_bourse'] = $indice['timestamp'];
      }
    }
  }

  public function sell()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $nb_metal = (isset($_POST['nb_metal'])) ? intval($_POST['nb_metal']) : 0;
    $nb_cristal = (isset($_POST['nb_cristal'])) ? intval($_POST['nb_cristal']) : 0;
    $nb_tetranium = (isset($_POST['nb_tetranium'])) ? intval($_POST['nb_tetranium']) : 0;
    $nb_energie = (isset($_POST['nb_energie'])) ? intval($_POST['nb_energie']) : 0;

    $user = $this->users->get($_SESSION['user']['user_id']);
    $actions = $this->bourse->getActionsFromUser($user);

    $count_metal = $count_cristal = $count_tetranium = $count_energie = 0;
    foreach ($actions AS $a)
    {
      $count_metal = ($a['type'] == "metal") ? $count_metal + 1 : $count_metal;
      $count_cristal = ($a['type'] == "cristal") ? $count_cristal + 1 : $count_cristal;
      $count_tetranium = ($a['type'] == "tetranium") ? $count_tetranium + 1 : $count_tetranium;
      $count_energie = ($a['type'] == "energie") ? $count_energie + 1 : $count_energie;
    }
    if ($count_energie < $nb_energie || $count_cristal < $nb_cristal || $count_tetranium < $nb_tetranium || $count_metal < $nb_metal)
      $this->template->redirect($this->template->language['not_enough_actions'], TRUE, "");
    $last = $this->bourse->getLast();
    $dbgolds = ($last['metal'] * $nb_metal) + ($last['cristal'] * $nb_cristal) + ($last['tetranium'] * $nb_tetranium) + ($last['energie'] * $nb_energie);
    foreach ($actions AS $a)
    {
      if ($nb_metal > 0 && $a['type'] == "metal")
      {
        $this->bourse->sell($a['id']);
        $nb_metal--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
      if ($nb_cristal > 0 && $a['type'] == "cristal")
      {
        $this->bourse->sell($a['id']);
        $nb_cristal--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
      if ($nb_tetranium > 0 && $a['type'] == "tetranium")
      {
        $this->bourse->sell($a['id']);
        $nb_tetranium--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
      if ($nb_energie > 0 && $a['type'] == "energie")
      {
        $this->bourse->sell($a['id']);
        $nb_energie--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
    }
    $this->template->actions = $this->bourse->getActionsFromUser($user);
    $this->template->setView("myactions");
    $this->bourse->save($last['metal'], $last['cristal'], $last['tetranium'], $last['energie']);
    $this->users->addDBGolds($user, $dbgolds);
    $this->template->redirect($this->template->language['bourse_sell_success'], FALSE, "");
  }

  public function exchange()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $nb_metal = (isset($_POST['nb_metal'])) ? intval($_POST['nb_metal']) : 0;
    $nb_cristal = (isset($_POST['nb_cristal'])) ? intval($_POST['nb_cristal']) : 0;
    $nb_tetranium = (isset($_POST['nb_tetranium'])) ? intval($_POST['nb_tetranium']) : 0;

    $user = $this->users->get($_SESSION['user']['user_id']);
    $actions = $this->bourse->getActionsFromUser($user);

    $count_metal = $count_cristal = $count_tetranium = 0;
    foreach ($actions AS $a)
    {
      $count_metal = ($a['type'] == "metal") ? $count_metal + 1 : $count_metal;
      $count_cristal = ($a['type'] == "cristal") ? $count_cristal + 1 : $count_cristal;
      $count_tetranium = ($a['type'] == "tetranium") ? $count_tetranium + 1 : $count_tetranium;
    }
    if ($count_cristal < $nb_cristal || $count_tetranium < $nb_tetranium || $count_metal < $nb_metal)
      $this->template->redirect($this->template->language['not_enough_actions'], TRUE, "");
    $last = $this->bourse->getLast();
    $_metal = (1000 * $nb_metal);
    $_cristal = (1000 * $nb_cristal);
    $_tetranium = (1000 * $nb_tetranium);
    foreach ($actions AS $a)
    {
      if ($nb_metal > 0 && $a['type'] == "metal")
      {
        $this->bourse->sell($a['id']);
        $nb_metal--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
      if ($nb_cristal > 0 && $a['type'] == "cristal")
      {
        $this->bourse->sell($a['id']);
        $nb_cristal--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
      if ($nb_tetranium > 0 && $a['type'] == "tetranium")
      {
        $this->bourse->sell($a['id']);
        $nb_tetranium--;
        $last[$a['type']] += (rand(1, 3) / 100);
      }
    }
    $this->template->actions = $this->bourse->getActionsFromUser($user);
    $this->template->setView("myactions");
    $this->bourse->save($last['metal'], $last['cristal'], $last['tetranium'], $last['energie']);
    $ressource = $this->ressources->get($_SESSION['user']['planet_id']);
    $r = array('tetranium' => $_tetranium, 'metaux' => $_metal, 'cristaux' => $_cristal);
    $this->ressources->add($ressource, $r);
    $this->template->redirect($this->template->language['bourse_sell_success'], FALSE, "");
  }

  public function buy()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");    
    $last = $this->bourse->getLast();
    $last_save = $last;
    $type = $_POST['type'];
    $number = intval($_POST['number']);
    if (!isset($_POST['type']) || !isset($_POST['number']) || !isset($last[$type]))
      $this->template->redirect($this->template->language['bourse_number_incorrect'], TRUE, "");
    $dbgolds = $last[$type] * $number;
    $user = $this->users->get($_SESSION['user']['user_id']);
    if (!$this->users->takeDBGolds($user, $dbgolds))
      $this->template->redirect($this->template->language['not_enough_dbgolds'], TRUE, "");
    for ($i = 0; $i < $number; $i++)
    {
      $this->bourse->buy($type, $user, $last_save);
      $last[$type] += (rand(1, 3) / 100);
    }
    $this->bourse->save($last['metal'], $last['cristal'], $last['tetranium'], $last['energie']);
    $this->template->actions = $this->bourse->getActionsFromUser($user);
    $this->template->setView("myactions");
    $this->template->redirect($this->template->language['bourse_buy_success'], FALSE, "");    
  }

  public function calcPrice()
  {
    $this->users->needLogin();
    $this->template->loadLanguage("board");
    $last = $this->bourse->getLast();
    $type = $_GET['type'];
    $number = intval($_GET['number']);
    if (!isset($_GET['type']) || !isset($_GET['number']) || !isset($last[$type]))
      $this->template->redirect($this->template->language['bourse_number_incorrect'], TRUE, "");
    $this->template->addJSON(array('last_quotation' => $last[$type], 'total' => ($last[$type] * $number)));
  }

}

?>
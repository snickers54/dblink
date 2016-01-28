<?php
/*
Copyright © <2011> <singler> <julien>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 US
*/

class				controller
{
  protected			$class;

  protected			$model;
  protected			$db;
  protected			$root;

  private			  $module;
  private	   		$action;
  private			  $models;
  private			  $controller;

  protected			$GET;
  protected			$POST ;
  protected			$FILES;
  
  protected			$needLogin	= 0;
  private			$jsArray	= "";
  private			$cssArray	= "";

  /**
   * @fn function __get($key)
   * @brief 
   * @file controller.php
   * 
   * @param key         
   * @return		
   */
  public function		__get($key) {return (isset($this->class[$key])) ? $this->class[$key] : NULL;}


  /**
   * @fn function init(&$rooter, &$objet)
   * @brief 
   * @file controller.php
   * 
   * @param rooter              
   * @param objet               
   * @return		
   */
  public function		init(&$rooter, &$objet)
  {
    include_once(dirname(__FILE__).'/library/redis/Autoloader.php');
    Predis\Autoloader::register();
    require_once("./".PATH_LIB."twig/lib/Twig/Autoloader.php");
    Twig_Autoloader::register();
    $dont = array("rooter" => 0, "error" => 0);
    include_once("model.php");
    $this->root = $rooter;
    $this->class['root'] = $rooter;
    $array = glob("./".PATH_LIB."*.php");

    foreach ($array AS $value)
      {
	      $temp = str_replace(".php", "", str_replace("./".PATH_LIB, "", $value));
      	if (array_key_exists($temp, $dont) === FALSE)
      	  $this->loadLibrary($temp);
      }
    $this->class['redis'] = new Predis\Client("tcp://127.0.0.1:6379");
    $this->init_variables();
    $this->model = $this->loadModel($this->models, $this->module);
    foreach ($this->class AS $obj)
      if (method_exists($obj, "loadLib"))
	       $obj->loadLib($this->class);
    $this->start($objet);
  }

  /**
   * @fn function init_variables()
   * @brief 
   * @file controller.php
   * 
   * @param             
   * @return		
   */
  private function		init_variables()
  {
    // URL
    $this->controller = $this->root->getController();
    $this->action = $this->root->getAction();
    $this->models = $this->root->getModel();
    $this->module = $this->root->getModule();

    // SUPERGLOBALES
    $this->GET = $this->root->getGET();
    $this->POST = $this->root->getPOST();
    $this->FILES = $this->root->getFILES();
  }

  /**
   * @fn function start($objet)
   * @brief 
   * @file controller.php
   * 
   * @param objet               
   * @return		
   */
  private function		start($objet)
  {
    $this->template->loadLanguage("header");
    $this->addJavascript("jquery-1.8.1.min");
    $this->addJavascript("header");
    $this->addJavascript("poll");
    $this->addJavascript("jquery.rebour");
    $this->addJavascript("wtooltip");
    if (EASYJQUERY)
      {
      	$this->addJavascript("framework");
      	$this->addJavascript("config");
      	$this->addJavascript("dialog");
	     $this->addJavascript("module");
      	$this->addCSS("dialog");
      }
    if (BOOTSTRAP){
      $this->addJavascript(PATH_BOOTSTRAP_JS."bootstrap");
      $this->addCSS(PATH_BOOTSTRAP_CSS."bootstrap");
      $this->addCSS(PATH_BOOTSTRAP_CSS."bootstrap-responsive");
    }
    $this->addCSS("style", "design");
    $this->initAction($objet);
    $this->template->jsArray = $this->jsArray;
    $this->template->cssArray = $this->cssArray;
    $this->template->module = str_replace("/", "", $this->module);
    $this->template->baseUrl = (strlen($this->template->module) > 0) ? "/".$this->template->module : "";
    $this->users->cookieConnect();
    $this->template->_current_time = time();
    $this->template->isLogged = $this->users->isLogged();
    if ($this->users->isLogged())
    {
      $user = $this->users->get($_SESSION['user']['user_id']);
      if ($this->root->isAjax() == FALSE)
      {
        if (($banned = $this->users->getValueConfig($user, "chat_ban")) == 0)
          self::getLastChat();
        else
          $this->template->banned_time = $banned;
      }
      $this->template->_user = $user->getDatas();
      $this->success->add($user, "premier_pas");
      $this->template->_new_mails = $this->mails->getNew($user->user_id);
      $this->template->_last_mails = $this->mails->getLast($user->user_id);
      $this->template->_new_rapports = $this->rapports->getNewRapports($user->getPlanet()->id);
      $this->template->_planet = $user->getPlanet()->getDatas();
      $this->template->_ressources = $this->ressources->get($_SESSION['user']['planet_id'])->getDatas();
      $this->template->_batiments = $this->batiments->get($_SESSION['user']['planet_id'])->getDatas();
      $this->template->_list_planets = $_SESSION['list_planets'];
      $this->template->_planet_avatar = $this->planetes->listAvatarPlanets($_SESSION['user']['user_id']);
      if ($_SESSION['user']['alliance_id'] > 0)
        $this->template->_alliance = $user->getAlliance()->getDatas();
    }
    $this->template->lang = strtolower((isset($_SESSION['user']['language'])) ? $_SESSION['user']['language'] : "FR");
    if ($this->root->isAjax() == FALSE)
      {
        //$this->users->migration();
      	$this->template->fetch($this->module);
      	$this->template->display();
      }
    else if ($this->root->isAjax() == TRUE)
      $this->template->fetchAjax($this->module);
  }

  protected function getLastChat()
  {
    $array = $this->chat->getBDD('chat');
    if ($array)
    foreach ($array AS $key => $val)
    {
      $array[$key] = $this->chat->loadUser($val['user_id'], $array[$key]);
      $array[$key]['msg'] = htmlspecialchars($val['msg']);
      $array[$key]['msg_wbbcode'] = htmlspecialchars($val['msg']);
    }
    $this->template->msg_chats = $array;
  }
  /**
   * @fn function initAction($objet)
   * @brief 
   * @file controller.php
   * 
   * @param objet               
   * @return		
   */
  private function		initAction($objet)
  {
    $pageController = $objet;
    if (!method_exists($pageController, $this->action))
      if ($this->root->isAjax() == TRUE)
	  		exit();
    $pageAction = $this->action;
    $pageController->$pageAction();
  }

  /**
   * @fn function loadClass($var)
   * @brief 
   * @file controller.php
   * 
   * @param var         
   * @return		
   */
  private function		loadClass($var)
  {
    $test = new $var($this->class);
    if ($test)
      $this->class[$var] = $test;
  }

  /**
   * @fn function loadLibrary($var)
   * @brief 
   * @file controller.php
   * 
   * @param var         
   * @return		
   */
  public function		loadLibrary($var)
  {
    $url = PATH_LIB.$var.".php";
    if (!file_exists($url))
    	return ;
    include_once($url);
    $this->loadClass($var);
  }

  /**
   * @fn function loadModel($var, $module = "")
   * @brief 
   * @file controller.php
   * 
   * @param var         
   * @param module              
   * @return		
   */
  public function		loadModel($var, $module = "")
  {
    $url = PATH_MODELS.$module.''.$var.".php";
    if (!file_exists($url)) 
    	return ;
    include_once($url);
    $var .= "Model";
    $this->loadClass($var);
    return $this->class[$var];
  }

  /**
   * @fn function addJavascript($url)
   * @brief 
   * @file controller.php
   * 
   * @param url         
   * @return		
   */
  public function		addJavascript($url)
  {
    if (!strstr($this->jsArray, JS."/".$url.".js"))
    {
     $this->jsArray .= "<script type=\"text/javascript\" src=\"".JS."/".$url.".js\"></script>\n";
    }
  }
  
  /**
   * @fn function addCSS($url, $title = "Css")
   * @brief 
   * @file controller.php
   * 
   * @param url         
   * @param title               
   * @return		
   */
  public function		addCSS($url, $title = "design") 
  {
    $this->cssArray .= "<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" title=\"".$title."\" href=\"".CSS."/".$url.".css\" />\n";
  }

}
?>

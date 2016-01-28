<?php

class		rooter
{
  private	$controller = "";
  private	$action = "";
  private	$module = "";
  private	$model = "";
  private	$GET;
  private	$POST;
  private	$FILES;
  private	$Ajax = FALSE;

  /**
   * @fn function __construct()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function __construct()
  {
    $this->GET = $this->clean($_GET);
    $this->POST = $this->clean($_POST);
    $this->FILES = $this->clean($_FILES);
  }

  /**
   * @fn function clean($data)
   * @brief 
   * @file rooter.php
   * 
   * @param data                
   * @return		
   */
  public function clean($data)
  {
    if (is_array($data))
      foreach ($data as $key => $value)
	$data[self::clean($key)] = self::clean($value);
    else 
      $data = stripslashes(htmlspecialchars($data));
    return $data;
  }
 
  /**
   * @fn function parseURI($requete)
   * @brief 
   * @file rooter.php
   * 
   * @param requete             
   * @return		
   */
  public function parseURI($requete)
  {
    $uri = substr($requete, 1); // on enleve le premier caractere c'est a dire le /
    $uri = str_replace("?".$_SERVER['QUERY_STRING'], "", $uri);    $array = explode("/", $uri); // on explose l'uri a partir du caractere /
    $v = 0;// initialisation
    // si le tableau contenant les fragment d'uri contient plus de 2 elements 
    if (($i = count($array)) > 2)
      {
	while ($i > 2) // tant qu'il ne reste pas que le controller et l'action
	  {
	    $this->module .= $array[$v]; // on concatene le path en ajoutant les /
	    $this->module .= "/";
	    $i--;
	    $v++;
	  }
      }
    if (strpos($array[$v], "?") === false) {
      $this->controller = $array[$v++]; // on recupere le controller    
    } else {
      $pos = strpos($array[$v], "?");
      $this->controller = substr($array[$v++], 0, $pos); // on recupere le controller    
    }
    $this->Ajax = FALSE;
    $this->checkAjaxRequest();
    $this->action = (isset($array[$v])) ? $array[$v] : NULL; // de meme pour l'action
    $pattern = "?".$_SERVER['QUERY_STRING'];
    $this->action = str_replace($pattern, "", $this->action); // on enleve les variables GET de l'action pour eviter d'avoir quelque chose comme toto?titi=tata
    if ($this->controller == NULL){$this->controller = "index";}
    if ($this->action == NULL){$this->action = "index";}
  }

  /**
   * @fn function getFILES()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getFILES()		{return $this->FILES;}

  /**
   * @fn function getGET()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getGET()		{return $this->GET;}

  /**
   * @fn function getPOST()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getPOST()		{return $this->POST;}

  /**
   * @fn function getController()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getController()	{return $this->controller;}
  /**
   * @fn function getAction()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getAction()		{return ($this->Ajax) ? $this->action : $this->action."Action";}

  /**
   * @fn function getModule()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getModule()		{return $this->module;}

  /**
   * @fn function getModel()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function getModel()		{return $this->controller;}

  /**
   * @fn function isAjax()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function isAjax()		{return $this->Ajax;}

  /**
   * @fn function checkAjaxRequest()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  private function checkAjaxRequest()
  {
    if ((isset($_GET['format']) && $_GET['format'] == "json") || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
      !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'))
      {
      	$this->Ajax = TRUE;
      	return TRUE;
      }
    return FALSE;
  }

  /**
   * @fn function checkErrorDispatch()
   * @brief 
   * @file rooter.php
   * 
   * @param             
   * @return		
   */
  public function checkErrorDispatch()
  {
    $view = PATH_VIEWS.$this->controller.".php";
    $model = PATH_MODELS.$this->controller.".php";
    $control = ($this->module) ? PATH_CONTROLLERS.$this->module.$this->controller.".php" : PATH_CONTROLLERS.$this->controller.".php";
    if (!file_exists($control))
      {
      	error::ErrorController();
      	exit();
      }
    return ;
  }
}
?>

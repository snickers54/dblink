<?php
class		template
{
  private	$_header = 'HeaderView.html';
  private	$_footer = 'FooterView.html';
  private	$data = array();
  private	$flux;
  private	$vue = array();
  private $module = array();
  private	$json = array();
  public	$language;
  private	$twig;

  /**
   * @fn function __construct($class)
   * @brief 
   * @file template.php
   * 
   * @param class               
   * @return		
   */
  public function __construct($class)
  {
      $this->root = $class['root'];
      if (!isset($_SESSION['lang']))
        $_SESSION['lang'] = "FR";
    if (!$this->root->isAjax())
    {
      if (isset($_SESSION['__error']) && $_SESSION['__error'])
        	$this->__set("__error", $_SESSION['__error']);
      if (isset($_SESSION['__success']) && $_SESSION['__success'])
        	$this->__set("__success", $_SESSION['__success']);
      if (isset($_SESSION['__achievement']) && $_SESSION['__achievement'])
          $this->__set("__achievement", $_SESSION['__achievement']);
      unset($_SESSION['__error']);
      unset($_SESSION['__success']);
      unset($_SESSION['__achievement']);

    }
      $loader = new Twig_Loader_Filesystem(PATH_VIEWS);
      $this->twig = new Twig_Environment($loader, array('cache' => false,'charset' => 'UTF-8'));
      $this->twig->addFilter('totime', new Twig_Filter_Function('template::convertTime'));
      $this->twig->addFilter('convertIcons', new Twig_Filter_Function('template::convertIcons'));
      $this->twig->addFilter('todistance', new Twig_Filter_Function('template::convertDistance'));
      $this->twig->addFilter('bbcode', new Twig_Filter_Function('template::bbcode'));
  }

    public static function bbcode($texte)
      {
      $texte = preg_replace('`\[b\](.+)\[/b\]`isU', '<strong>$1</strong>', $texte); 
      $texte = preg_replace('`\[i\](.+)\[/i\]`isU', '<em>$1</em>', $texte);
      $texte = preg_replace('`\[s\](.+)\[/s\]`isU', '<u>$1</u>', $texte);
      $texte = preg_replace('`\[u\](.+)\[/u\]`isU', '<u>$1</u>', $texte);
      $texte = preg_replace('#\[quote=(&\#039;|&quot;|"|\'|)(.*?)\\1\]#e', '"<small>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), \'$2\')." a ecrit:</small><blockquote><p>"', $texte);
      $texte = preg_replace('#\[quote\]\s*#', '</p><blockquote>', $texte);
      $texte = preg_replace('#\s*\[\/quote\]#S', '</p></blockquote>', $texte);
      $texte = preg_replace('/\[url=(.*)\](.*)\[\/url\]/U','<a href="$1" target="_blank" class="label important">$2</a>',$texte);
      $texte = preg_replace('`\[r\](.+)\[/r\]`isU', '<strike>$1</strike>', $texte);
      $texte = preg_replace('/\[img\](.*)\[\/img\]/U','<img class="image_chat" src="$1" alt="image" />',$texte);
      $texte = preg_replace('`\:\)`', '<img src="/public/images/chat/sourire.png" />', $texte);
      $texte = str_replace(':P', '<img src="/public/images/chat/langue.png" />', $texte);
      $texte = str_replace(':p', '<img src="/public/images/chat/langue.png" />', $texte);
      $texte = str_replace('^^', '<img src="/public/images/chat/hihi.png" />', $texte);
      $texte = str_replace(':D', '<img src="/public/images/chat/heureux.png" />', $texte);
      $texte = str_replace(':d', '<img src="/public/images/chat/heureux.png" />', $texte);
      $texte = str_replace(';)', '<img src="/public/images/chat/wink.png" />', $texte);
      $texte = str_replace(':o', '<img src="/public/images/chat/huh.png" />', $texte);
      $texte = str_replace(':O', '<img src="/public/images/chat/huh.png" />', $texte);
      $texte = str_replace(':mdr:', '<img src="/public/images/chat/rire.gif" />', $texte);
      $texte = str_replace(':euh:', '<img src="/public/images/chat/euh.gif" />', $texte);
      $texte = str_replace(':triste:', '<img src="/public/images/chat/triste.png" />', $texte);
      $texte = str_replace(":'(", '<img src="/public/images/chat/triste.png" />', $texte);
      $texte = str_replace(':@', '<img src="/public/images/chat/colere.png" />', $texte);
      $texte = str_replace(':colere:', '<img src="/public/images/chat/colere.png" />', $texte);
      $texte = str_replace(':hein:', '<img src="/public/images/chat/hein.gif" />', $texte);
      $texte = str_replace(':lala:', '<img src="/public/images/chat/siffle.png" />', $texte);

      // gestion des rapports
    if (preg_match_all('#\[rapport\=[0-9]+\]#', $texte, $a))
      {
        foreach ($a[0] AS $array)
        {
          preg_match('#[0-9]+#', $array, $b);
          $id = abs(intval($b[0]));
          $texte = preg_replace('#\[rapport\=[0-9]+\]#', "<span class='label label-important histo_chat cursor' rapport_id='".$id."' click='board:getEmbedRapport'>Rapport #".$id." &rarr;</span>", $texte, 1);
        }
      }

    if (preg_match_all('#\[empire\=[0-9]+\]#', $texte, $a))
      {
        foreach ($a[0] AS $array)
        {
          preg_match('#[0-9]+#', $array, $b);
          $id = abs(intval($b[0]));
          $texte = preg_replace('#\[empire\=[0-9]+\]#', "<span class='label label-important histo_chat cursor' rapport_id='".$id."' click='board:getEmbedEmpire'>Empire #".$id." &rarr;</span>", $texte, 1);
        }
      }

      return $texte;
}

  public static function convertIcons($string)
  {
    switch ($string)
    {
      case "metal":
        return "<i class='icon-magnet icon-white'></i>";
      case "cristal":
        return "<i class='icon-cog icon-white'></i>";
      case "energie":
        return "<i class='icon-signal icon-white'></i>";
      case "population":
        return "<i class='icon-user icon-white'></i>";
      case "tetranium":
        return "<i class='icon-tint icon-white'></i>";
      case "lmetal":
        return "<i class='icon-magnet icon-white'></i> limit";
      case "lcristal":
        return "<i class='icon-cog icon-white'></i> limit";
      case "lpopulation":
        return "<i class='icon-user icon-white'></i> limit";
      case "ltetranium":
        return "<i class='icon-tint icon-white'></i> limit";
    }
    return "";
  }

  public static function convertDistance($string)
  {
    if ($string > 1000)
    {
      $mod = $string % 1000;
      $string -= $mod;
      $string /= 1000;
      $string += ($mod / 1000);
      return $string." kpc";
    }
    return $string." pc";
  }

  public static function convertTime($string)
  {
    $secondes = intval($string);
    $temp = $secondes % 3600;
    $t['heures'] = round(($secondes - $temp) / 3600);
    $t['secondes'] = round($temp % 60);
    $t['minutes'] = round(($temp - $t['secondes']) / 60);
    $t['jours'] = (int) ($t['heures'] / 24);
    $t['heures'] = $t['heures'] % 24;
    $retour = "";
    if ($t['jours'] > 0)
      $retour .= $t['jours']."j ";
    
    if ($t['heures'] > 0)
      $retour .= $t['heures']."h ";
    else if ($t['jours'] > 0)
      $retour .= "0h ";
    
    if ($t['minutes'] > 0)
      $retour .= $t['minutes']."m ";
    else if ($t['heures'] > 0)
      $retour .= "0m";

    if ($t['secondes'] > 0)
      $retour .= $t['secondes']."s";
    return $retour;
  }

  /**
   * @fn function redirect($msg, $isError, $url = "SELF")
   * @brief 
   * @file template.php
   * 
   * @param msg         
   * @param isError             
   * @param url         
   * @return		
   */
  public function redirect($msg, $isError, $url = "SELF")
  {
    if ($this->root->isAjax())
      {
      	if ($isError)
      	  $array = array("_error_" => $msg);
      	else
          $array = array("_success_" => $msg);
      	$this->addJSON($array);
      	$this->fetchAjax();
      	return ;
      }
    if ($isError == TRUE)
      $this->setError($msg);
    else
      $this->setSuccess($msg);
    if ($url == "SELF")
      $url = str_replace("?".$_SERVER['QUERY_STRING'], "", $_SERVER['REQUEST_URI']);
    header("Location: ".$url);
    exit();
  }

  /**
   * @fn function setError($str)
   * @brief 
   * @file template.php
   * 
   * @param str         
   * @return		
   */
  private function setError($str) {$_SESSION['__error'] = $str;}

  /**
   * @fn function setSuccess($str)
   * @brief 
   * @file template.php
   * 
   * @param str         
   * @return		
   */
  private function setSuccess($str) {$_SESSION['__success'] = $str;}


  public function setAchievement($str, $class)
  {
    $_SESSION['__achievement']['msg'] = $str;
    $_SESSION['__achievement']['class'] = $class;
  }
  /**
   * @fn function __get($key)
   * @brief 
   * @file template.php
   * 
   * @param key         
   * @return		
   */
  public function __get($key) {
    return isset($this->data[$key]) ? $this->data[$key] : NULL;
  }

  /**
   * @fn function __set($key, $value)
   * @brief 
   * @file template.php
   * 
   * @param key         
   * @param value               
   * @return		
   */
  public function __set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * @fn function fetch($module = "", $disableHeader = FALSE)
   * @brief 
   * @file template.php
   * 
   * @param module              
   * @param disableHeader               
   * @return		
   */
  public function fetch($module = "")
  {
    if (extension_loaded("deflate"))
      ob_start('ob_gzhandler');
    else
       ob_start();      
    $this->loadView($module);
    $this->flux = ob_get_contents();
    ob_end_clean();
  }

  /**
   * @fn function addJSON($array)
   * @brief 
   * @file template.php
   * 
   * @param array               
   * @return		
   */
  private function	json_clean($data)
  {
    if (is_array($data))
      foreach ($data AS $key => $val)
	     $data[$key] = $this->json_clean($val);
    else
      $data = utf8_encode($data);
    return $data;
  }

  public function addJSON($array)
  {
    if (is_array($array))
      	$this->json = array_merge($this->json, $array);
  }
  /**
   * @fn function fetchAjax($module = "")
   * @brief 
   * @file template.php
   * 
   * @param module              
   * @return		
   */

  public function fetchAjax($module = "")
  {
    header("Content-Type: application/json");
    if (isset($_SESSION['__achievement']))
    {
      self::addJSON(array("_achievement_" => $_SESSION['__achievement']));
      unset($_SESSION['__achievement']);
    }
    if ($this->countView() > 0)
      {
        if (extension_loaded("deflate"))
          ob_start('ob_gzhandler');
        else
           ob_start();  
      	$this->loadView($module);
      	$this->json['_html_'] = ob_get_contents();
      	ob_end_clean();
      }
    echo json_encode($this->json);
    exit;
  }
  public function changeHeader($file)
  {
    if ($file !== false)
    {
      $file .= ".html";
      $this->_header = $file;
    }
    else 
      $this->_header = false;
  }

  public function	changeFooter($file)
  {
    if ($file !== false)
    {
      $file .= ".html";
      $this->_footer = $file;
    }
    else
      $this->_footer = false;
  }
  /**
   * @fn function display()
   * @brief 
   * @file template.php
   * 
   * @param             
   * @return		
   */
  public function display() {echo $this->flux;}

  /**
   * @fn function has($key)
   * @brief 
   * @file template.php
   * 
   * @param key         
   * @return		
   */
  public function has($key) {return isset($this->data[$key]);}

  /**
   * @fn function getData()
   * @brief 
   * @file template.php
   * 
   * @param             
   * @return		
   */
  public function getData() {return $this->data;}

  public function getTwig() {return $this->twig;}

  /**
   * @fn function setView($var)
   * @brief 
   * @file template.php
   * 
   * @param var         
   * @return		
   */
  public function setView($var, $module = FALSE) {
    $this->vue[$var] = $var;
    if ($module === FALSE)
      $module = $this->root->getModule();
    $this->module[$var] = $module;
  }

  /**
   * @fn function countView()
   * @brief 
   * @file template.php
   * 
   * @param             
   * @return		
   */
  public function countView() {
    $i = 0;
    foreach ($this->vue AS $views)
      {
      	$url = $this->module[$views].''.$views.".html";
      	if (file_exists(PATH_VIEWS.$url))
      	  $i++;
      }
    return $i;
  }

  /**
   * @fn function loadView($module, $disableHeader = false)
   * @brief 
   * @file template.php
   * 
   * @param module              
   * @param disableHeader               
   * @return		
   */
  public function loadView($module)
  {
    if ($this->_header !== false && $this->root->isAjax() == false)
      echo $this->twig->render($this->_header, $this->data);
    foreach ($this->vue AS $views)
      {
	     $url = $this->module[$views].''.$views.".html";
    	 if (file_exists(PATH_VIEWS.$url))
          echo $this->twig->render($url, $this->data);
      }
      if ($this->_footer !== false && $this->root->isAjax() == false)
        echo $this->twig->render($this->_footer, $this->data);
  }

  /**
   * @fn function loadLanguage($controller)
   * @brief 
   * @file template.php
   * 
   * @param lang                
   * @param controller          
   * @return		
   */
  public function       	loadLanguage($controller)
  {
    $lang = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : "FR";
    $url = PATH_LANG.$lang."/".$controller.".php";
    if (!file_exists($url))
    	return;
    require_once($url);
    if (isset($_) && !is_array($_))
      $_ = array();
    if (is_array($this->language))
      $this->language = array_merge($this->language, $_);
    else
      {
      	$this->language = $_;
      	$this->data['_lang'] = &$this->language;
      }
    unset($_);
    unset($url);
    unset($controller);
  }
}
?>
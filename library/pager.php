<?php
class			pager
{
  private		$results = array();
  private		$class;
  private		$number_per_page = 20;
  private		$nb_page_max = 0;
  private		$actual_page = 0;

  /**
   * @fn function __construct($class)
   * @brief 
   * @file pager.php
   * 
   * @param class               
   * @return		
   */

  public function __construct() {
  }

  public function loadLib($class) {
    if (is_array($class))
      foreach ($class AS $key => $value)
	$this->$key = $value;
  }

  /**
   * @fn function __get($key)
   * @brief 
   * @file pager.php
   * 
   * @param key         
   * @return		
   */
  public function __get($key)
  {
    return ((isset($this->class[$key])) ? $this->class[$key] : NULL);
  }

  /**
   * @fn function __set($key, $val)
   * @brief 
   * @file pager.php
   * 
   * @param key         
   * @param val         
   * @return		
   */
  public function __set($key, $val)
  {
    $this->class[$key] = $val;
  }

  /**
   * @fn function setDatas($data)
   * @brief set all datas (array) in the class pager
   * @file pager.php
   * 
   * @param data                
   * @return		
   */
  public function	setDatas($data)
  {
    if (!is_array($data))
      {
    	 error::errorPager();
	     return;
      }
    $this->result = $data;
  }
  /**
   * @fn function getDatas()
   * @brief give all datas
   * @file pager.php
   * 
   * @param             
   * @return		
   */

  public function	getDatas()
  {
    return $this->result;
  }

  /**
   * @fn function getResult($page, $number_per_page)
   * @brief give result filter by page and number of elements per page
   * @file pager.php
   * 
   * @param page                
   * @param number_per_page             
   * @return		
   */

  public function	getResult($page = 1, $number_per_page = 20)
  {
    if (isset($_GET['page']))
      $page = abs(intval($_GET['page']));
    $keys = array_keys($this->result);
    $this->number_per_page = $number_per_page;
    $this->nb_page_max = (int) (count($keys) / $number_per_page) + 1;
    if ($page > $this->nb_page_max)
      $page = $this->nb_page_max;
    $this->actual_page = $page;
    $position_start = $number_per_page * ($page - 1);
    $position_end = $position_start + $number_per_page;

    if ($position_end > count($keys))
      $position_end = count($keys);

    $array_result = array();
    for ($i = $position_start; $i < $position_end; $i++)
      $array_result[] = $this->result[$keys[$i]];
    return (empty($array_result)) ? FALSE : $array_result;
  }

  /**
   * @fn function getPageFromID($key)
   * @brief get page from id 
   * @file pager.php
   * 
   * @param key         
   * @return		
   */
  public function	getPageFromID($id)
  {
    $array = array_keys($this->result);
    $key = 1;
    foreach ($array AS $k => $v)
      {
	if ($v == $id)
	  break;
	$key++;
      }
    return ((int) ($key / $this->number_per_page)) + 1;
  }

  /**
   * @fn function getPagination($url)
   * @brief create html div with pagination dynamique
   * @file pager.php
   * 
   * @param url         
   * @return		
   */
  public function	getPagination($url)
  {
    if (count($this->result) <= 0)
      return "";

    $url = str_replace("?".$_SERVER['QUERY_STRING'], "", $url);
    $get = "?page=";
    if (!isset($_GET['page']))
      $_GET['page'] = '';
    if (!empty($_SERVER['QUERY_STRING']))
      {
	$_SERVER['QUERY_STRING'] = str_replace("page=".$_GET['page'], "", $_SERVER['QUERY_STRING']);
	$_SERVER['QUERY_STRING'] = str_replace("&page=".$_GET['page'], "", $_SERVER['QUERY_STRING']);
	if (!empty($_SERVER['QUERY_STRING']))
	  $get = "?".$_SERVER['QUERY_STRING']."&page=";
      }
    $html = '<div class="pager">';
    if (($end = $this->actual_page + 10) > $this->nb_page_max)
      $end = $this->nb_page_max;
    if (($this->actual_page - 10) > 1)
      $html .= '<a href="'.$url.''.$get.''.($this->actual_page - 11).'"><img src="/public/images/prev.png" alt="prev"/></a> ';
    for ($i = ($this->actual_page - 10); $i < $end; $i++)
      {
	if ($i < 0)
	  $i = 0;
	if (($i - 1) == $this->actual_page)
	  $html .= '<span class="pager_apage">'.($i + 1).'</span>';
	else
	  $html .= '<a class="pager_npage" href="'.$url.''.$get.''.($i + 1).'">'.($i + 1).'</a> ';
      } 
    if (($this->actual_page + 10) < $this->nb_page_max)
      $html .= '<a href="'.$url.''.$get.''.($this->actual_page + 11).'"><img src="/public/images/next.png" alt="next"/></a>';
    $html .= "</div>";
    return $html;
  }
}
?>
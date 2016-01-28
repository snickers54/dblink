<?php

class		db
{
  private			$_serveur;
  private			$_user;
  private			$_pass;
  private static	$_db = false;
  private			$class;

  // ici on fait un multi ton
  /**
   * @fn function __construct($class)
   * @brief 
   * @file db.php
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
   * @file db.php
   * 
   * @param key         
   * @return		
   */
  public function __get($key)
  {
    return (isset($this->class[$key])) ? $this->class[$key] : NULL;
  }

  /**
   * @fn function __set($key, $val)
   * @brief 
   * @file db.php
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
   * @fn function connect($serveur, $user, $pass, $bd)
   * @brief 
   * @file db.php
   * 
   * @param serveur             
   * @param user                
   * @param pass                
   * @param bd  	
   * @return		
   */
  private function connect($serveur, $user, $pass, $bd)
  {
    $this->_db = mysql_connect($serveur, $user, $pass);    
    if (!$this->_db) {
      error::ErrorSQL("Echec de connection a la base sql");
    }
    if (!mysql_select_db($bd)) {
      error::ErrorSQL("Echec de connection a la base sql");
    }
    mysql_set_charset('utf8', $this->_db);
    mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

    return ($this->_db);
  }

  /**
   * @fn function getInstance($serveur, $user, $pass, $bd)
   * @brief 
   * @file db.php
   * 
   * @param serveur             
   * @param user                
   * @param pass                
   * @param bd  	
   * @return		
   */
  public function getInstance($serveur, $user, $pass, $bd)
  {
    if (!isset($this->_db)) {
      $this->_db = $this->connect($serveur, $user, $pass, $bd);
    }
    return ($this->_db);
  }

  /**
   * @fn function getLastId() // permet de recuperer la derniere clef primaire insere via mysql_insert_id()
   * @brief 
   * @file db.php
   * 
   * @param             
   * @return		
   */
  public function getLastId() // permet de recuperer la derniere clef primaire insere via mysql_insert_id();
  {
    return (mysql_insert_id($this->_db));
  }

  /**
   * @fn function query($query)
   * @brief 
   * @file db.php
   * 
   * @param query               
   * @return		
   */
  public function query($query) //
  {
    $res = mysql_query($query);
    if (!$res) {
      error::ErrorSQL("Erreur base sql", $query);
    }
    if (is_resource($res))
      {
      	$ret = new stdClass;
      	$ret->count = mysql_num_rows($res);
      	$ret->query = $res;

      	$ret->rows = array();
      	$i = 0;
      	while (($resultat = mysql_fetch_assoc($res))) {
      	  $ret->rows[$i] = $resultat;
      	  $i++;
      	}
      	$ret->row = isset($ret->rows[0]) ? $ret->rows[0] : array();
      	return ($ret);
      }
    return true;
  }

  /**
   * @fn function escape($value)
   * @brief 
   * @file db.php
   * 
   * @param value               
   * @return		
   */
  public function escape($value) {return mysql_real_escape_string($value);}

  /**
   * @fn function escape_html($value)
   * @brief 
   * @file db.php
   * 
   * @param value               
   * @return		
   */
  public function escape_html($value) {return htmlentities($value);}
}
?>
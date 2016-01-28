<?php 
class	webservices
{
  // debut prologue LIB
	private $class;

  public function	__construct($class)
  {
  }

	public function loadLib($class) {
    if (is_array($class))
      foreach ($class AS $key => $value)
         $this->$key = $value;
  }

 public function __get($key)
  {
    return (isset($this->class[$key])) ? $this->class[$key] : NULL;
  }

  public function __set($key, $val)
  {
    $this->class[$key] = $val;
  }

  public function addUserID($token, $user_id)
  {
    $this->redis->hset($token, "user_id", $user_id);
    $this->redis->expire($token, 30 * HOUR);
  }

  public function getUserID($token)
  {
    $user_id = $this->redis->hget($token, "user_id");
    if (!$user_id)
      $this->template->redirect($this->template->language['webservices_token_invalid'], TRUE, "");
    return $user_id;
  }
}
// fin prologue lib
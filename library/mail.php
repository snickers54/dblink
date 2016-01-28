<?php
class		mail
{
  //this function is used for send mail. The first parameters is the dest (string) but he can to be a array.
  //For that to be a array, $tab must be equal true else one mail will be send.
  //the second parameters is the subject of the mail.
  //$head is for additional headers. For example, Cc, BCc, From, ...
  //$param is for additional parameters. For example -f.
  private	$class;

  /**
   * @fn function __construct($class)
   * @brief 
   * @file mail.php
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

  //this function is used for the error with the send mail.
  private function		gestionError($mess, $subject, $destinataires)
  {
  	if (!isset($mess))
		  error::ErrorMail("corps du mail");
  	if (!isset($subject))
  		error::ErrorMail("sujet du mail");
  	if (!isset($destinataires))
  		error::ErrorMail("destinataire");
  }
  /**
   * @fn function __get($key)
   * @brief 
   * @file mail.php
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
   * @file mail.php
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
   * @fn function sendMail($dest, $subject, $mess, $head = "", $param = "")
   * @brief 
   * @file mail.php
   * 
   * @param dest                
   * @param subject             
   * @param mess                       
   * @param head                
   * @param param		
   * @return		
   */
  public function		sendMail($dest, $subject, $mess, $head = "", $param = "")
  {
    if (isset($mess))
		$mess = wordwrap($mess, 70); //this is for the function mail because the mess must be to have less of 70 chars
    if (is_array($dest) == true)	{
		$destinataires = "";
		for ($i = 0; $dest[$i]; $i++)	{
			$destinataires .= $dest[$i];
			if ($dest[$i + 1])
				$destinataires .= ", ";
		}
    }
    else
      $destinaires = $dest;
	  
	$this->gestionError($mess, $subject, $destinataires);
    if ((!isset($head)) && (!isset($param)))
      mail($destinataires, $subject, $mess);
    else if (!isset($head))
      mail($destinataires, $subject, $mess, null, $param);
    else if (!isset($param))
      mail($destinataires, $subject, $mess, $head);
  }
  
  //this function is used for send mail with html. There is just a difference with the function precedently, we add a few lines in the headers.
  /**
   * @fn function sendMailHtml($dest, $subject, $mess, $head = "", $param = "")
   * @brief 
   * @file mail.php
   * 
   * @param dest                
   * @param subject             
   * @param mess                        
   * @param head                
   * @param param		
   * @return		
   */
  public function		sendMailHtml($dest, $subject, $mess, $head = "", $param = "")
  {
	if (isset($mess))
		$mess = wordwrap($mess, 70); //this is for the function mail because the mess must be to have less of 70 chars
    if (is_array($dest) == true)	{
    	$destinataires = "";
    	for ($i = 0; $dest[$i]; $i++)	{
    		$destinataires .= $dest[$i];
    		if ($dest[$i + 1])
    			$destinataires .= ", ";
		}
    }
    else
      $destinaires = $dest;
	$this->gestionError($mess, $subject, $destinataires);
    if (isset($head) == true)	{
      $head  = 'MIME-Version: 1.0' . "\r\n";
      $head .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    }
    else	{
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= $head;
		$head = $headers;
    }
    if (isset($param))
    	mail($destinataires, $subject, $mess, $head, $param);
    else
    	mail($destinataires, $subject, $mess, $head);
	}
	
	
	
}
?>
<?php
class	userModel extends Model
{
	public function checkLoginExist($login)
	{
		$res = $this->db->query('SELECT `user_id` FROM `user` WHERE `login` = "'.$login.'"');
		if ($res->count > 0)
			return false;
		return true;
	}

  public function checkEmailExist($email)
  {
    $res = $this->db->query('SELECT `user_id` FROM `user` WHERE `email` = "'.$email.'"');
    if ($res->count == 1)
      return $res->row['user_id'];
    return false;
  }
  
  public function   getFilleuls($user_id)
  {
    $res = $this->db->query('SELECT `user_id`, `avatar`, `login` FROM `user` WHERE `parrain_id` = "'.$user_id.'"');
    if ($res->count > 0)
      return $res->rows;
    return FALSE;
  }
  public function   getFriends($user_id)
  {
    $res = $this->db->query('SELECT u.`user_id`, u.`avatar`, u.`login` FROM `amis` a 
      LEFT JOIN `user` u ON ((u.user_id = a.user_id1 AND a.user_id1 != "'.$user_id.'") OR (u.user_id = a.user_id2 AND a.user_id2 != "'.$user_id.'")) WHERE a.user_id1 = "'.$user_id.'" OR a.user_id2 = "'.$user_id.'"');
    if ($res->count > 0)
      return $res->rows;
    return FALSE;    
  }

  public function   updateUserDescription($nom, $prenom, $background, $user_id)
  {
    $this->db->query('UPDATE `user_description` SET `nom` = "'.$nom.'", `prenom` = "'.$prenom.'", `background` = "'.$background.'" WHERE `user_id` = "'.$user_id.'"');
  }

  public function   getUsersList()
  {
    $query = $this->db->query('SELECT `user_id`, `login` FROM `user`');
    return $query->rows;
  }
}
?>
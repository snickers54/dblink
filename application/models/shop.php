<?php
class	shopModel extends Model
{
  public function	notification($user_id, $montant)
  {
    $user_id = intval($user_id);
    $montant = intval($montant);
    $this->db->query('INSERT INTO `paiements_allopass` SET `user_id` = "'.$user_id.'",
    					`montant` = "'.$montant.'", date = NOW()');
  }
}
?>
<?php
class	indexModel extends Model
{
  public function		getLastEvent()
  {
    $query = $this->db->query('SELECT * FROM `news` WHERE `type` = "EVENTS" AND `time_end` > "'.time().'"');
    if ($query->count > 0)
      return $query->rows;
    return FALSE;
  }

  public function		getLastNews()
  {
    $query = $this->db->query('SELECT * FROM `news` WHERE `type` = "NEWS" ORDER BY `id` DESC, `type` DESC');
    if ($query->count > 0)
      return $query->rows;
    return FALSE;
  }

}
?>
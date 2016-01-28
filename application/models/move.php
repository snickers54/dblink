<?php
class	moveModel extends Model
{
	public function getAllPlanets($user_id)
	{
		$query = $this->db->query('SELECT * FROM `planete` WHERE `user_id` = "'.$user_id.'" AND `active` = 1 ORDER BY `ordre` ASC');
		return $query->rows;
	}
}

?>
<?php

class webservicesModel extends Model
{
	function getPlanets($user_id)
	{
		$req = $this->db->query('SELECT `nom`, `id` FROM `planete` WHERE `user_id` = "'.$user_id.'"');
		return $req;
	}
}
?>
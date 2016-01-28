<?php
class	scriptModel extends Model
{
	public function getAllPlanetes()
	{
		$res = $this->db->query('SELECT id FROM `planete` WHERE `user_id` > 0 ');
		return $res->rows;
	}

	public function getBatiment($code)
	{
		$res = $this->db->query('SELECT * FROM `batiments_type` WHERE `code` = "'.$code.'"');
		if ($res->count != 1)
			return NULL;
		return $res->row;
	}

	public function getBatimentTechnoCodes()
	{
		$res = $this->db->query('SELECT `code`, `nom` FROM `batiments_type` WHERE `type` = "batiments" OR `type` = "technologie"');
		return $res->rows;
	}
}
?>
<?php
class adminModel extends Model
{
	public function getAllBuildings()
	{
		$query = $this->db->query('SELECT * FROM `batiments_type` order by `code` ASC');
		return $query->rows;
	}

	public function getAllSuccess()
	{
		$query = $this->db->query('SELECT * FROM `achievements` order by nom ASC');
		return $query->rows;
	}
	public function getAllMission()
	{
		$query = $this->db->query('SELECT * FROM `mission` order by title ASC');
		return $query->rows;
	}
	public function getAllTerrains()
	{
		$query = $this->db->query('SELECT * FROM `planete_type`');
		return $query->rows;
	}
	public function getIDAllPlanets($user_id)
	{
		$query = $this->db->query('SELECT `id` FROM `planete` WHERE `user_id` = "'.$user_id.'"');
		if ($query->count > 0)
			return $query->rows;
		return NULL;
	}

	public function getAllUsers()
	{
		$query = $this->db->query('SELECT `login`, `user_id` FROM `user` ORDER BY `login` ASC');
		return $query->rows;
	}

	public function deleteBuilding($id)
	{
		$this->db->query('DELETE FROM `batiments_type` WHERE `id` = "'.$id.'"');
	}

	public function deleteSuccess($id)
	{
		$this->db->query('DELETE FROM `achievements` WHERE `id` = "'.$id.'"');
	}
	public function deleteMission($id)
	{
		$this->db->query('DELETE FROM `mission` WHERE `id` = "'.$id.'"');
	}
	public function deleteTerrains($id)
	{
		$this->db->query('DELETE FROM `planete_type` WHERE `id` = "'.$id.'"');
	}

	public function modifTerrains($array)
	{
		$id = $array['id'];
		unset($array['id']);
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('UPDATE `planete_type` SET '.$set.' WHERE `id`= "'.$id.'"');
	}

	public function modifSuccess($array)
	{
		$id = $array['id'];
		unset($array['id']);
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('UPDATE `achievements` SET '.$set.' WHERE `id`= "'.$id.'"');
	}

	public function modifBuilding($array)
	{
		$id = $array['id'];
		unset($array['id']);
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('UPDATE `batiments_type` SET '.$set.' WHERE `id`= "'.$id.'"');
	}
	public function modifMission($array)
	{
		$id = $array['id'];
		unset($array['id']);
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('UPDATE `mission` SET '.$set.' WHERE `id`= "'.$id.'"');
	}
	public function createBuilding($array)
	{
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('INSERT INTO `batiments_type` SET '.$set.'');
	}
	public function createSuccess($array)
	{
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('INSERT INTO `achievements` SET '.$set.'');
	}
	public function createTerrains($array)
	{
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('INSERT INTO `planete_type` SET '.$set.'');
	}

	public function createMission($array)
	{
		$set = "";
		$i = 0;
		foreach ($array AS $key => $value)
		{
			if ($i > 0)
				$set .= ", ";
			$set .= "`".$key.'` = "'.$this->db->escape($value).'"';
			$i ++;
		}
		$this->db->query('INSERT INTO `mission` SET '.$set.'');
	}
}

?>
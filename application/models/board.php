<?php
class	boardModel extends Model
{

	public function createBatimentRapport($user_id, $planet_id, $json, $template)
	{
		$this->db->query('INSERT INTO `batiments_rapports` SET `timestamp` = "'.time().'", `user_id` = "'.$user_id.'", `planet_id` = "'.$planet_id.'", `json` = "'.$this->db->escape($json).'", `template` = "'.$template.'"');
		return $this->db->getLastId();
	}

	public function getLastBatimentsRapport($planet_id)
	{
		$res = $this->db->query('SELECT br.*, p.nom as planete_name, u.login FROM `batiments_rapports` br 
			LEFT JOIN planete p ON p.id = br.planet_id
			LEFT JOIN user u ON u.user_id = br.user_id
			WHERE br.`planet_id` = "'.$planet_id.'" ORDER BY br.`id` DESC LIMIT 1');
		if ($res->count > 0)
			return $res->row;
		return NULL;
	}

	public function getBatimentsRapport($id)
	{
		$res = $this->db->query('SELECT br.*, p.nom as planete_name, u.login FROM `batiments_rapports` br
			LEFT JOIN planete p ON p.id = br.planet_id
			LEFT JOIN user u ON u.user_id = br.user_id
			 WHERE br.`id` = "'.$id.'"');
		if ($res->count > 0)
			return $res->row;
		return NULL;
	}
	public function getAll()
	{
		$query = $this->db->query('SELECT * FROM `planete`');
		return $query->rows;
	}

	public function get($galaxie)
	{
		$this->db->query('CALL calc_angle');
		$res = $this->db->query('SELECT * FROM `planete` WHERE `galaxie` = "'.$galaxie.'"');
		return $res->rows;
	}

	public function distinctGalaxies()
	{
		$query = $this->db->query('SELECT DISTINCT(galaxie) FROM planete ORDER BY galaxie ASC');
		return $query->rows;
	}

	public function getWeather($planet_id)
	{
		$query = $this->db->query('SELECT `weather` FROM `planete` WHERE `id` = "'.$planet_id.'"');
		if ($query->count == 1)
			return $query->row['weather'];
		return NULL;
	}

	public function getMissions()
	{
		$res = $this->db->query('SELECT * FROM `mission`');
		return $res->rows;
	}
}
?>
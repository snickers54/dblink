<?php
class allianceModel extends model
{
	public function getAllianceList() {
		$ret = $this->db->query("SELECT * from alliance");
		return $ret;
	}

	public function getAllyInfosById($id) {
		$ret = $this->db->query("SELECT a.*, s.*, u.login, g.nom as 'gradeName' FROM `alliance` a 
									left join alliance_status s on a.id = s.id_alliance
									left join alliance_grade g on s.id_grade = g.id
									left join user u on u.user_id = s.id_user where a.id = '".$id."' && g.candidat = 0");
		return $ret->rows;
	}

	public function getMyRights($id) {
		$ret = $this->db->query("SELECT s.id_grade, g.* from alliance_status s left join alliance_grade g on s.id_grade = g.id where s.id_user = '".$id."'");
		return $ret->row;		
	}
	public function		getAccords($type, $my_id) {
    $query = $this->db->query('SELECT * FROM `accords` WHERE (`alliance_id_1` = "'.$my_id.'" OR `alliance_id_2` = "'.$my_id.'") AND `type` = "'.$type.'"');
    if ($query->count > 0)
      {
	$i = 0;
	foreach ($query->rows AS $value)
	  {
	    $array[$i]['id'] = $value['id'];
	    $array[$i]['date'] = $value['date'];
	    $array[$i]['date_create'] = $value['date_create'];
	    $array[$i]['my_status'] = $value['alliance_status_2'];
	    $array[$i]['their_status'] = $value['alliance_status_1'];
	    $array[$i]['alliance_id'] = $value['alliance_id_1'];
	    if ($value['alliance_id_1'] == $my_id)
	      {
		$array[$i]['their_status'] = $value['alliance_status_2'];
		$array[$i]['alliance_id'] = $value['alliance_id_2'];
		$array[$i]['my_status'] = $value['alliance_status_1'];
	      }
	    $array[$i]['their_name'] = $this->getNameFromIDAlliance($array[$i]['alliance_id']);
	    if ($array[$i]['their_name'] == false)
	    	{
	    		unset($array[$i]);
	    		$i--;
	    	}
	    $i++;
	  }
	return $array;
      }
    return FALSE;

  }


	public function		getNameFromIDAlliance($id) {
    	$query = $this->db->query('SELECT `nom` FROM `alliance` WHERE `id` = "'.$id.'" LIMIT 1');
    	if ($query->count > 0)
    	  return $query->row['nom'];
    	return false;    
  	}

  	public function updateAlly($value, $id, $item) {
  		switch ($item){

  			case "nom":
 			$this->db->query("UPDATE alliance set nom = '".$this->db->escape($value)."' where id = '".$id."'");
 			break;
 			case "tag":
 			$this->db->query("UPDATE alliance set tag = '".$this->db->escape($value)."' where id = '".$id."'");
 			break;
 			case  "description":
 			$this->db->query("UPDATE alliance set description = '".$this->db->escape($value)."' where id = '".$id."'");
 			break;
 			case  "welcome":
 			$this->db->query("UPDATE alliance set welcome_text = '".$this->db->escape($value)."' where id = '".$id."'");
 			break;
 			case  "citation":
 			$this->db->query("UPDATE alliance set citation = '".$this->db->escape($value)."' where id = '".$id."'");
 			break;
 			case  "url":
 			$this->db->query("UPDATE alliance set url_forum = '".$this->db->escape($value)."' where id = '".$id."'");
 			break;
  		}
  	}

  	public function getGrades($id) {
		$ret = $this->db->query("SELECT * from alliance_grade where id_alliance = '".$id."'");
		return $ret->rows; 		
  	}

  	public function getAllyNews($id) {
  		$ret = $this->db->query("SELECT a.*, u.login as 'author' from alliance_news a 
  			left join user u on a.id_user = u.user_id where id_alliance = '".$id."' order by date desc");
  		return $ret->rows; 		
  	}

  	public function addNews($array, $user_id, $alliance_id) {
		$this->db->query("INSERT into alliance_news set title='".$this->db->escape($array['title'])."', content = '".$this->db->escape($array['content'])."', id_user = '".$user_id."', date=NOW(), id_alliance = '".$alliance_id."'");  			
  	}

  		public function editNews($array) {
		$this->db->query("UPDATE alliance_news set title='".$this->db->escape($array['title'])."', content = '".$this->db->escape($array['content'])."' where id = '".$array['id']."'");  			
  	}

  	public function deleteNews($id) {
  	$this->db->query("delete from alliance_news where id = '".$id."'");  		
  	}

  	public function getActus($id) {
  		$ret = $this->db->query("select * from alliance_actu where id_alliance = '".$id."' order by date DESC");
  		return $ret->rows;
  	}

  		public function getPublicActus($id) {
  		$ret = $this->db->query("select * from alliance_actu where id_alliance = '".$id."' && type=1 order by date DESC");
  		return $ret->rows;
  	}

  	public function addActus($array, $alliance_id) {
  		$this->db->query("insert into alliance_actu set id_alliance = '".$alliance_id."', type='".$array['type']."', icon = '".$array['icon']."', date = NOW(), content = '".$this->db->escape($array['content'])."'");		
  	}

  	public function requestAlly($id_alliance, $id_user) {
  		$this->db->query("insert into alliance_status set id_alliance = '".$id_alliance."', id_user = '".$id_user."', id_grade = (select id from alliance_grade where id_alliance= '".$id_alliance."' && candidat = 1)");
  	}

  	public function getNbCandidats($id_alliance) {
  		$ret = $this->db->query("SELECT COUNT(*) as 'nb' from alliance_status where id_alliance = '".$id_alliance."' && id_grade = (select id from alliance_grade where candidat = 1 && id_alliance = '".$id_alliance."')");
  		return $ret->row['nb'];
  	}

  	public function modifyRank($id_user, $id_grade, $id_alliance) {
  		$this->db->query("UPDATE alliance_status set id_grade = '".$id_grade."' where id_user = '".$id_user."' && id_alliance = '".$id_alliance."'");
  	}

    public function getCandidats($id_alliance) {
      $ret = $this->db->query("SELECT a.*, u.login from alliance_status a left join user u on a.id_user = u.user_id where a.id_alliance = '".$id_alliance."' && a.id_grade = (select id from alliance_grade where candidat = 1 && id_alliance = '".$id_alliance."')");
      return $ret;
    }
  
    public function acceptMember($id_user, $id_alliance) {
      $this->db->query("UPDATE alliance_status set id_grade = (select id from alliance_grade where new = 1 && id_alliance = '".$id_alliance."') where id_user = '".$id_user."'");
      $this->db->query("UPDATE alliance set nb_membre = nb_membre + 1 where id = '".$id_alliance."'");
    }

    public function rejectMember($id_user, $id_alliance) {
      $this->db->query("delete from alliance_status where id_alliance = '".$id_user."' && id_user = '".$id_user."'");
    }
  }
?>
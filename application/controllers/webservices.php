<?php
class webservicesController extends controller
{
	public function login()
	{
		$this->template->loadLanguage("user");
	  	if (isset($this->GET['login']) && isset($this->GET['password']) &&
			    ($user_id = $this->users->connect($this->GET['login'], $this->GET['password'])))
	  	{
	  		$token = md5(SALT.$this->GET['password']);
	  		$this->webservices->addUserID($token, $user_id);
	  		$this->template->addJSON(array("token" => $token));
	    	$this->template->redirect($this->template->language['login_success'].$this->GET['login'], FALSE, "");
	 	}
	    $this->template->redirect($this->template->language['login_fail'], TRUE, "");
	}

	public function updatePlanet()
	{
		$user_id = $this->webservices->getUserID($_GET['token']);
		$planet_id = $_GET['planet_id'];
		$name = $_GET['name'];
		$note = $_GET['notes'];

		$planet = $this->planetes->get($planet_id);
		$planet->nom = $name;
		$planet->note = $note;
		$this->planetes->save($planet);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");	
	}

	public function getPlanet()
	{
		$user_id = $this->webservices->getUserID($_GET['token']);
		$planet = $this->planetes->get($_GET['planet_id']);
		$array = $planet->getDatas();
		$r = $this->ressources->get($_GET['planet_id']);
		$r->refresh($planet->weather);
		$array['ressources'] = $r->getDatas();
		$this->template->addJSON($array);
	}

	public function getPlanets()
	{
		$user_id = $this->webservices->getUserID($_GET['token']);
		$req = $this->model->getPlanets($user_id);
		$array = array();
		foreach ($req->rows AS $p)
		{
			$array[$p['id']] = $this->planetes->get($p['id'])->getDatas();
			$r = $this->ressources->get($p['id']);
			$r->refresh($p['weather']);
			$array[$p['id']]['ressources'] = $r->getDatas();
		}
		$this->template->addJSON($array);
	}
}
?>
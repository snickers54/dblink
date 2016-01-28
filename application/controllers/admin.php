<?php

class adminController extends controller
{
	public function buildingsAction()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->template->buildings = $this->model->getAllBuildings();
		$this->template->setView("adminListBuildings");
	}
	public function modifBuilding()
	{
		$this->users->needLogin(ADMIN);
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->modifBuilding($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function deleteBuilding()
	{
		$this->users->needLogin(ADMIN);
		$this->model->deleteBuilding($_POST['id']);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function createBuilding()
	{
		$this->users->needLogin(ADMIN);
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->createBuilding($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}


	public function usersAction()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("user");
		$this->template->users = $this->model->getAllUsers();
		$this->template->setView("adminListUsers");
	}

	public function modifUser()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("user");
		$user = $this->users->get(intval($_POST['user']['user_id']));
		$a_user = $_POST['user'];
		$user->login = $a_user['login'];
		$user->email = $a_user['email'];
		$user->avatar = $a_user['avatar'];
		if (strlen($a_user['password']) > 0)
			$user->password = md5(SALT.$a_user['password']);
		$this->users->save($user);
		if (isset($_POST['planet']))
			foreach ($_POST['planet'] AS $planet_id => $value)
			{
				$ressources = $this->ressources->get($planet_id);
				$ressources->metaux = $value['metaux'];
				$ressources->cristaux = $value['cristaux'];
				$ressources->population = $value['population'];
				$ressources->tetranium = $value['tetranium'];
				$this->ressources->save($ressources);
			}
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function getUser()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("user");
		$this->template->loadLanguage("index");
		$id = intval($_GET['id']);
		$user = $this->users->get($id);
		$ids = $this->model->getIDAllPlanets($id);
		$planets = array();
		if ($ids)
			foreach ($ids AS $value)
			{
				$planet_id = $value['id'];
				$p = $this->planetes->get($planet_id);
				$planets[$planet_id] = $p->getDatas();
				$planets[$planet_id]['ressources'] = $p->getRessources()->getDatas();
			}
		$this->template->planets = $planets;
		$u = $user->getDatas();
		$obj = $this->loadModel("user");
		$u['filleuls'] = $obj->getFilleuls($user->user_id);
    	$u['amis'] = $obj->getFriends($user->user_id);
		$u['connected'] = ($this->redis->keys("user_".$id)) ? "yes" : "no";
		if ($u['alliance_id'] > 0)
			$u['alliance'] = $this->alliances->get($u['alliance_id'])->getDatas();
		$this->template->u = $u;
		$this->template->setView("adminGetUser");
	}

	public function deleteUser()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("user");
		$this->users->delete(intval($_POST['id']));
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");

	}

	public function activateUser()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("user");
		$this->users->activate(intval($_POST['id']));
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");

	}

	public function successAction()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->template->success = $this->model->getAllSuccess();
		$this->template->setView("adminListSuccess");
	}

	public function deleteSuccess()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->model->deleteSuccess(intval($_POST['id']));
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function modifSuccess()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->modifSuccess($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}


	public function createSuccess()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->createSuccess($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function createMission()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->createMission($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}
	public function missionAction()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->template->mission = $this->model->getAllMission();
		$this->template->setView("adminListMission");
	}

	public function deleteMission()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->model->deleteMission(intval($_POST['id']));
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function modifMission()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->modifMission($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function terrainsAction()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->template->terrains = $this->model->getAllTerrains();
		$this->template->setView("adminListTerrains");
	}

	public function deleteTerrains()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		$this->model->deleteTerrains(intval($_POST['id']));
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}

	public function modifTerrains()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->modifTerrains($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}


	public function createTerrains()
	{
		$this->users->needLogin(ADMIN);
		$this->template->loadLanguage("board");
		unset($_POST['_url_']);
		unset($_POST['_type_']);
		$this->model->createTerrains($_POST);
		$this->template->redirect($this->template->language['modif_success'], FALSE, "");
	}
}

?>
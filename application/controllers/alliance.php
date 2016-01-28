<?php

/**
* class alliance
*/
class allianceController extends controller
{
	
	function indexAction()
	{
		$this->users->needLogin();
		$this->template->allianceList = $this->model->getAllianceList()->rows;
		$this->template->setView("allianceList");
	}

	public function viewAllyAction() {
		$this->users->needLogin();
		if (!isset($this->GET['id']))
			$this->template->redirect("No ally selected", TRUE, "/alliance");
		$id = $this->GET['id'];
		$this->template->infos = $this->model->getAllyInfosById($id);
		$this->template->war = $this->model->getAccords(WAR, $id);
		$this->template->pacts = $this->model->getAccords(PACTE, $id);
		$this->template->actus = $this->model->getPublicActus($id);
		$this->template->setView("allianceView");
	}

	public function requestAllyAction() {
		$this->users->needLogin();
		if ($_SESSION['user']['alliance_id'] != 0)
			$this->template->redirect("vous avez déja une alliance !", TRUE, "/alliance/");
		$alliance_id = $_GET['id'];
		$this->model->requestAlly($alliance_id, $_SESSION['user']['user_id']);
		$array['type'] = 0;
		$array['content'] = $_SESSION['user']['login']. " a postulé dans votre alliance";
		$array['icon'] = "icons/glyphicons_006_user_add.png";
		$this->model->addActus($array, $alliance_id);
		$this->template->redirect("vous avez postulé avec succes", FALSE, "/alliance/");
	}

	public function modifyRank() {
		$this->users->needLogin();
		$id_grade = $_POST['id_grade'];
		$id_user = $_POST['id'];
		$this->model->modifyRank($id_user, $id_grade, $_SESSION['user']['alliance_id']);	
		$array['type'] = 1;
		$array['content'] = "le grade de ". $_POST['login']. " a été modifié en " .$_POST['grade'] ." par " . $_SESSION['user']['login'];
		$array['icon'] = "icons/glyphicons_069_gift.png";
		$this->model->addActus($array, $_SESSION['user']['alliance_id']);
		$data['_success_'] = "grade modifié avec succes";
		$this->template->addJSON($data);
	}

	public function acceptMember() {
		$id = $_POST['id'];
		$this->model->acceptMember($id, $_SESSION['user']['alliance_id']);
		$array['type'] = 0;
		$array['content'] = $_POST['login']. " a rejoint l'alliance !";
		$array['icon'] = "icons/glyphicons_006_user_add.png";
		$this->model->addActus($array, $_SESSION['user']['alliance_id']);
		$data['_success_'] = "Membre accepté avec succes";
		$this->template->addJSON($data);
	}

	public function rejectMember() {
		$id = $_POST['id'];
		$this->model->rejectMember($id, $_SESSION['user']['alliance_id']);
		$data['_success_'] = "Membre refusé avec succes";
		$this->template->addJSON($data);
	}
	
	public function myAllyAction() {
		
		$this->users->needLogin();
		if ($_SESSION['user']['alliance_id'] == 0)
			$this->template->redirect("vous n'avez pas d'alliance !", TRUE, "/alliance/");
		$this->template->infos = $this->model->getAllyInfosById($_SESSION['user']['alliance_id']);
		$this->template->rights = $this->model->getMyRights($_SESSION['user']['user_id']);
		$this->template->war = $this->model->getAccords(WAR, $_SESSION['user']['alliance_id']);
		$this->template->pacts = $this->model->getAccords(PACTE, $_SESSION['user']['alliance_id']);
		$this->template->grades = $this->model->getGrades($_SESSION['user']['alliance_id']);
		$this->pager->setDatas($this->model->getAllyNews($_SESSION['user']['alliance_id']));
 		$this->template->news = $this->pager->getResult(1, 5);
 		$this->template->actus = $this->model->getActus($_SESSION['user']['alliance_id']);
 		$this->template->newsPagination = $this->pager->getPagination("/alliance/myAlly");
 		$candidats = $this->model->getNbCandidats($_SESSION['user']['alliance_id']);
 		if ($candidats > 0)
 			$this->template->candidatsList = $this->model->getCandidats($_SESSION['user']['alliance_id'])->rows;
 		$this->template->candidats = $candidats;
		$this->template->setView("myAllyView");
	}

	public function updateItem() {
		$this->users->needLogin();
		$this->model->updateAlly($this->POST['value'], $_SESSION['user']['alliance_id'], $_POST['item']);
		$data['_success_'] = $this->POST['item'] . " changé avec succes";
		$this->template->addJSON($data);
	}

	public function postNews() {
		$array['title'] = $_POST['title'];
		$array['content'] = $_POST['content'];
		$this->model->addNews($array, $_SESSION['user']['user_id'], $_SESSION['user']['alliance_id']);
		$data['_success_'] = "News postée avec succes";
		$array['type'] = 0;
		$array['content'] = "Une news a été postée par " . $_SESSION['user']['login'];
		$array['icon'] = "icons/glyphicons_417_rss.png";
		$this->model->addActus($array, $_SESSION['user']['alliance_id']);
		$this->template->addJSON($data);
		
	}

	public function editNews() {
		$this->model->editNews($_POST);
		$data['_success_'] = "News éditée avec succes";
		$this->template->addJSON($data);		
	}

	public function deleteNews() {
		$this->model->deleteNews($_POST['id']);
		$data['_success_'] = "News suprimée avec succes";
		$this->template->addJSON($data);
	}


}
?>

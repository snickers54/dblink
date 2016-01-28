<?php

class indexController extends controller
{
  public function indexAction()
  {
    $this->template->setView("index");
  }

  public function newsAction()
  {
  	$this->users->needLogin();
  	$this->template->news = $this->model->getLastNews();
  	$this->template->events = $this->model->getLastEvent();
  	$this->template->current_timestamp = time();
  	$this->template->setView("news");
  }

  public function teamAction()
  {
    $this->users->needLogin();
    $this->template->setView("team");
  }
}
?>

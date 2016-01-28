<?php
class shopController extends controller
{
  public function indexAction()
  {
    $this->users->needLogin();
    $this->template->setView("shop");
  }

  public function valideAction()
  {
    $docID = intval($_GET['docId']);
    $uid = $_GET['uid'];
    $trid = $_GET['trId'];
    $hash = $_GET['hash'];
    $hashkey = "V3DBLINK";
    $temp = $uid . $awards . $trid . $hashkey;
    $real_hash = md5($temp);
    $nb = intval($_GET['awards']);
    if ($hash == $real_hash)
    {
      $this->model->notification($uid, $nb);
      $user = $this->users->get($uid);
      $this->users->addDBGolds($user, $nb);
    }
 }
}
?>

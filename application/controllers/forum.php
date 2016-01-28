<?php
class			forumController extends controller
{


  public function indexAction()
  {
  
  $this->users->needLogin();
	//$this->addCSS("forum", "design");
	$ret = $this->forum->getEverything();
$_SESSION['user']['forum_rights'] = 0;
    $cat = 0;
    $i = 0;
    foreach ($ret->rows as $data)
    {
      if (empty($data['name_forum'])){
        continue;
        $i++;
      }
      
      if ($cat != $data['id_categorie'])
      {
        $ret->rows[$i]['name_cat'] = stripslashes(htmlspecialchars($data['name_cat']));
        $cat = $data['id_categorie'];
      }
      if (!empty($data['moderators']) && $data['moderators'] != NULL)
      {
        $tmp= unserialize($data['moderators']);
        if ($tmp != NULL)
        $ret->rows[$i]['moderators'] = implode(", ", $tmp);
        else
          $ret->rows[$i]['moderators'] = NULL;
      }
      $ret->rows[$i]['name_forum'] = stripslashes($data['name_forum']);
      $ret->rows[$i]['desc'] = nl2br(stripslashes($data['desc']));

  $user = $this->users->get($_SESSION['user']['user_id']);

      if ($_SESSION['user']['forum_rights'] < $ret->rows[$i]['right_view']&& !$this->users->isAdmin($user) && !$this->users->isModo($user))
        $ret->rows[$i]['view'] = false;
      else
         $ret->rows[$i]['view'] = true;
       $i++;
    }

    $this->template->ret = $ret;
  $this->template->setView("forum");
  }
  
  public function prepareView($ret)
  {
    $view = "";
    $view .= "<table>";
    $cat = 0;
    $mess = 0;
    $i = 0;

    foreach ($ret->rows as $data)
    {
      if (empty($data['name_forum'])){
        continue;
      }
      if ($cat != $data['id_categorie'])
      {
        $data['name_cat'] = stripslashes(htmlspecialchars($data['name_cat']));
        $cat = $data['id_categorie'];
      }
      $data['moderators'] = unserialize($data['moderators']);
      $data['name_forum'] = stripslashes(htmlspecialchars($data['name_forum']));
      $data['desc'] = nl2br(stripslashes(htmlspecialchars($data['desc'])));
    }

    $this->template->ret = $ret;

    /*foreach ($ret->rows as $data)
    {
      if (empty($data['name_forum'])){
        continue;
      }
      if ($cat != $data['id_categorie'])
      {
        $view .= '<tr>
          <th ><strong>'.stripslashes(htmlspecialchars($data['name_cat'])).'
          </strong></th>             
          <th ><strong>Sujets</strong></th>       
          <th ><strong>Messages</strong></th>       
          <th ><strong>Dernier message</strong></th>   
          </tr>';
        $cat = $data['id_categorie'];
      }
      $mess += $data['nb_reponses'] + $data['nb_topics'];
      $modo = unserialize($data['moderators']);
      $mod ="";
      $view .= "<tr><td class=\"titre\"><strong><a href='/forum/voirForum?id=".$data['id_forum1']."'>".stripslashes(htmlspecialchars($data['name_forum']))."</a></strong><br/>".nl2br(stripslashes(htmlspecialchars($data['desc'])));  
      if (!empty($modo))
      {
        $view .="<br />moderateurs :";  
        $mod = implode(", ", $modo);
        $view .= $mod;
      }
      $view .= "</td><td >".$data['nb_topics']."</td><td >".$data['nb_reponses']."</td><td ><a href=\"/forum/viewTopic?id=".$data['id_topic1']."&amp;post=".$data['last_post']."\">last message</a></td></tr>";
    }
    $view .="</table>";*/
    return $view;
  }


  public function voirForumAction()
  {
     $this->users->needLogin();
  $this->addCSS("forum", "design");
	$id = intval($_GET['id']);
	if (!$this->forum->forumExist($id))
	echo 'error';
 $user = $this->users->get($_SESSION['user']['user_id']);
	$this->template->topics = $this->forum->getTopicsFromForum($id);
  $this->template->infos = $this->forum->getForumById($id)->row;
  $this->template->id = $id;
  $i = 0;
  if ($_SESSION['user']['forum_rights'] < $this->template->infos['right_view'] && !$this->users->isAdmin($user) && !$this->users->isModo($user))
     $this->template->redirect("Vous n'avez pas les droits pour voir ce forum", TRUE, "/forum/");
  foreach ($this->template->topics->rows as $value)
  {

    $value['name'] = stripslashes($value['name']);
    $value['name'] = $this->forum->bbcode($value['name']);
    $value['date'] = date("Y/M/D", ($value['date']));
    $this->template->topics->rows[$i] = $value;
    $i++;
  }
   $modo = unserialize($this->template->infos['moderators']) ;
   $flag = true;
   if ($modo != false)
  {
  foreach ($modo as $value) {
    if ($value == $user->login)
    {
      $flag = true;
      break;
    }
  }
}
  if ($flag == false && $_SESSION['user']['forum_rights'] < $this->forum->getConfigFromKey("right_admin") && !$this->users->isAdmin($user) && !$this->users->isModo($user))
  {
    $this->template->viewModerator = false;
  }
  else
    $this->template->viewModerator = true;
	$this->template->setView("forumView");
	
  }
  
  public function deletePostAction()
  {
	$id = intval($_GET['id']);
	  $this->checkOwner($id);
	if (!$this->forum->postExist($id))
	echo 'error';
	$this->forum->deletePost($id);
    $topic = intval($_GET['topic']);
	$this->template->redirect("post supprime avec succes", FALSE, "/forum/viewTopic?id=".$topic);
  }
  
  
  public function editPostAction()
  {
	$id = intval($_GET['id']);
	  $this->checkOwner($id);
	if (!$this->forum->postExist($id))
		$this->template->redirect("ce post n'existe pas", TRUE, getReferer());
	$post = $this->forum->getPost($id)->row;
  
  $this->template->post =str_replace("<br />","",$post);
  $this->template->infos = $this->forum->getArianeFromPost($id)->row;
	$this->template->setView("editPost");
  }
  
  public function DoEditPostAction()
  {
     $this->users->needLogin();
  $id = intval($_GET['id']);
  $this->checkOwner($id);
 
  if (!isset($_POST['answer']))
	$this->template->redirect("le message est vide.", TRUE, getReferer());

  $message = mysql_real_escape_string(nl2br(htmlentities($_POST['answer'])));
  $this->forum->editPost($id, $message);
  $this->template->redirect("message editer avec succes.", FALSE, "/forum/");
  }
  
  public function	createTopicAction()
  {	
     $this->users->needLogin();
     $user = $this->users->get($_SESSION['user']['user_id']);
  if (!isset($_GET['id']))
   $this->template->redirect("ce forum n'existe pas", TRUE, "/forum");
  $forum_id = intval($_GET['id']);
  if (!$this->forum->forumExist($forum_id))
  $this->template->redirect("ce forum n'existe pas", TRUE, "/forum");
  $ret = $this->forum->getForumById($forum_id);
  $configRightAdmin = $this->forum->getConfigFromKey("right_post_admin");
   $configRightAnnonce = $ret->row['right_annonce'];
  if ($ret->row['right_create'] > $_SESSION['user']['forum_rights'] && !$this->users->isAdmin($user) && !$this->users->isModo($user))
    $this->template->redirect("vous n'avez pas les permissions necessaire", TRUE, "/forum/");

  if (isset($_POST['answer']) || isset($_POST['titre']))
  {
	 if (!isset($_POST['answer']) || empty($_POST['answer']))
	 {
	   $this->template->titre = $_POST['titre'];
	   $this->template->redirect("vous n'avez pas ecris de message", TRUE, "/forum/createTopic?id=".$forum_id);
	 }
	 else if (!isset($_POST['titre']) || empty($_POST['titre']))
	 {
	  $this->template->message = $_POST['answer'];
	  $this->template->redirect("vous n'avez pas ecris de message", TRUE, "/forum/createTopic?id=".$forum_id);
	 }
	 else
	 {
     if ($_POST['type'] != "normal")
     {
       if ($_POST['type'] == "Annonce" && $_SESSION['user']['forum_rights'] < $configRightAnnonce && !$this->users->isAdmin($user) && !$this->users->isModo($_user))
       $this->template->redirect("vous n'avez pas les permissions necessaire pour poster ce sujet en tant qu'annonce", FALSE, "/forum/createTopic?id=".$forum_id);
         if ($_POST['type'] == "Admin" && $_SESSION['user']['forum_rights'] < $configRightAdmin && !$this->users->isAdmin($user) && !$this->users->isModo($user))
       $this->template->redirect("vous n'avez pas les permissions necessaire pour poster ce sujet en tant qu'Admin", FALSE, "/forum/createTopic?id=".$forum_id);
     }
	   $message = mysql_real_escape_string($_POST['answer']);
     $titre = mysql_real_escape_string($_POST['titre']);
     $type = $_POST['type'];

     $id_topic = $this->forum->createTopic($forum_id, $titre, $message, $user->login, $type);
     $this->template->redirect("Sujet cree avec succes", FALSE, "/forum/viewTopic?id=".$id_topic);
	 }
  }
  else
  {
	 if (empty($this->template->message))
	   $this->template->message = "";
	 if (empty($this->template->titre))
	   $this->template->titre = "";
  
   
   $this->template->canAdmin = ($_SESSION['user']['forum_rights'] >= $configRightAdmin || $this->users->isAdmin($user) || $this->users->isModo($user)) ? true : false;
   $this->template->canAnnonce = ($_SESSION['user']['forum_rights'] >= $configRightAnnonce || $this->users->isAdmin($user) || $this->users->isModo($user)) ? true : false;
	 $this->template->id = $forum_id;
   $this->template->forum_name = $ret->row['name'];
	 $this->template->setView("createTopic");
  }
  }
  
  public function checkOwner($id)
  {
    $user = $this->users->get($_SESSION['user']['user_id']);
  if (!$this->forum->amIOwner($id, $user->login) && $_SESSION['user']['forum_rights'] < 1 && !$this->users->isAdmin($user) && !$this->users->isModo($user))
    $this->template->redirect("vous n'avez pas les permission necessaire", TRUE, $this->getReferer()); 
  }
  
  public function moderateAction()
  {
     $this->users->needLogin();
     $user = $this->users->get($_SESSION['user']['user_id']);
    $id = intval($_GET['id']);
      if (!$this->forum->forumExist($id))
    echo 'error';
  $this->addJavascript("forum");
  $this->template->infos = $this->forum->getForumById($id)->row;
  $modo = unserialize($this->template->infos['moderators']) ;

  $flag = false;
  if ($modo != false)
  {
  foreach ($modo as $value) {
    if ($value == $user->login)
    {
      $flag = true;
      break;
    }
  }
}
  if ($flag == false && $_SESSION['user']['forum_rights'] < $this->forum->getConfigFromKey("right_admin") && !$this->users->isAdmin($user) && !$this->users->isModo($user))
  {
    $this->template->redirect("Vous n'avez pas les permissions necessaire", TRUE, "/forum/voirForum?id=".$id);
  }

  if (isset($_POST['lock']) || isset($_POST['unlock']))
  {
    $this->lockTopic($_POST, $id);  
  }
  else if (isset($_POST['move']))
  {
    $this->moveTopic($_POST, $id);
  }
  else if (isset($_POST['moveTo']))
  {
    $this->moveTopicTo($_POST['topics'], $_POST['to'], $id);
  }
  else
  {
  $this->template->topics = $this->forum->getTopicsFromForum($id);
  $this->template->linkCreate = '<a href="/forum/createTopic?id='.$id.'">Nouveau Topic</a>';
  $i = 0;
  foreach ($this->template->topics->rows as $value)
  {

    $value['name'] = stripslashes($value['name']);
    $value['date'] = date("Y/M/D", ($value['date']));
    $this->template->topics->rows[$i] = $value;
    $i++;
  }
  $this->template->setView("forumModerateView");
}
  }

  public function lockTopic($a, $id)
  {
    $lock = (isset($a['lock'])) ? 1 : 0;
    if (empty($a['topic']))
       $this->template->redirect("Aucun topic selectionne", TRUE, "/forum/moderate?id=".$id);
     $array_id = @array_keys($a['topic']);
     $this->forum->lockTopicArray($array_id, $lock);
    ($lock == 1) ? $this->template->redirect("Topic lock avec succes", TRUE, "/forum/moderate?id=".$id) : $this->template->redirect("Topic unlock avec succes", TRUE, "/forum/moderate?id=".$id); 

  }

  public function moveTopic($a, $id)
  {
    if (empty($a['topic']))
      $this->template->redirect("Aucun topic selectionne", TRUE, "/forum/moderate?id=".$id);
    $this->template->topics = implode(',', @array_keys($a['topic']));
     $this->template->infos = $this->forum->getForumById($id)->row;
     $this->template->info = $this->forum->getForumsAndCat()->rows;
    $this->template->setView("moveTo");
  }

  public function moveTopicTo($from, $to, $id)
  {
    $id_array = explode(',', $from);
    foreach ($id_array as $value) {
      $this->forum->moveTopic($value, $to);
    }
     $this->template->redirect("Topics deplace avec succes", TRUE, "/forum/moderate?id=".$id);
  }

  public function getReferer()
  {
    //var_dump($_SERVER);
  $ref = "/forum/";
  //tester si $ref contient l'adresse du site.
  //sinon on redirige vers l'index.
  return $ref;
  }
  
  public function postAnswerAction()
  {
     $this->users->needLogin();
    $id_topic = intval($_GET['id']);
$user = $this->users->get($_SESSION['user']['user_id']);
    if (!isset($_GET['id']) || !$this->forum->topicExist($id_topic))
    {
      $this->template->redirect("ce topic n'existe pas ou plus", TRUE, "/forum");
    }

    if ($this->forum->isLocked($id_topic) && !$this->users->isAdmin($user) && !$this->users->isModo($user))
    {
       $this->template->redirect("ce topic est verrouiller", TRUE, "/forum/viewTopic?id=".$id_topic);
    }

    if (isset($_POST['answer']))
    {
      $_POST['answer'] = trim($_POST['answer']);
      
      if (!empty($_POST['answer']))
      {
      $message = mysql_real_escape_string(nl2br(htmlentities($_POST['answer'])));
      $id_post = $this->forum->createPost($message, $user->login, $id_topic, false);
      $this->template->redirect("Message poste avec succes", FALSE, "/forum/viewTopic?id=".$id_topic."&post=".$id_post);
      }
    else
    {
      $this->template->topic_id = $id_topic;
      $this->template->setView("answerEditor");
    }
  }
  }

  public function getMessageByIdAction()
  {
    $id = intval($this->POST['id']);
    $ret = $this->forum->getPostById($id);
    $data = array();
    $data['id'] = $ret['id'];
    $data['author'] = $this->POST['author'];
    $data['message'] =$ret['message'];
    $this->template->addJSON($data);
  }

  public function viewTopicAction()
  {
     $this->users->needLogin();
    $this->addCss('documentation/highlight');
    $this->addJavascript('documentation/highlight');
  $id_post = 0;
  $user = $this->users->get($_SESSION['user']['user_id']);
 
  $id_topic = intval($_GET['id']);
   //$this->addCSS("forum", "design");
  $this->addJavascript("forum");
	if (!$this->forum->topicExist($id_topic)){
	$this->template->redirect("ce topic n'existe pas ou plus", TRUE, "/forum");
}
	$post = $this->forum->getPostsFromTopic($id_topic);
  $name = $this->forum->getArianeFromTopic($id_topic)->row;
  if ($_SESSION['user']['forum_rights'] < $name['right_view']&& !$this->users->isAdmin($user) && !$this->users->isModo($user))
     $this->template->redirect("Vous n'avez pas les droits pour voir ce topic", TRUE, "/forum/");
  $name['topic_name'] = $this->forum->bbcode($name['topic_name']);
  $this->template->info = $name;
	$this->forum->addView($id_topic);
  $posts = array();
    if (isset($_GET['post']))
    {
   $id_post = intval($_GET['post']);
   $result = array();
   foreach ($post->rows as $value) {
     $result[$value['id']] = $value;
   }

   $this->pager->setDatas($result);
   $page = $this->pager->getPageFromID($id_post);
   //echo $id_post."<br>".$page;
   //exit(1);
   $this->template->redirect("", FALSE, "/forum/viewTopic?id=".$id_topic."&page=".$page."#".$id_post);
  }
  else
  $this->pager->setDatas($post->rows);
	$posts = $this->pager->getResult();
	$this->template->next = $this->pager->getPagination($_SERVER["REQUEST_URI"]);
  

  $i = 0;
  $this->template->posts = array();
  $this->template->canAnswer = ($_SESSION['user']['forum_rights'] >= $this->template->info['right_post'] || $this->users->isAdmin($user) || $this->users->isModo($user)) ? true : false;
  $this->template->infos = $this->forum->getForumById($name['forum_id'])->row;
  $modo = unserialize($this->template->infos['moderators']) ;

  $flag = false;
  if ($modo != false)
  {
  foreach ($modo as $value) {
    if ($value == $user->login)
    {
      $flag = true;
      break;
    }
  }
}
if ($flag == false && $_SESSION['user']['forum_rights'] < $this->forum->getConfigFromKey("right_admin")&& !$this->users->isAdmin($_user) && !$this->users->isModo($user))
$this->template->canModerate = false;
else
$this->template->canModerate = true;
  if ($this->forum->isLocked($id_topic))
    $this->template->canAnswer = false;
  foreach ($posts as $value)
  {

    $posts[$i]['date'] = date("Y/M/D", ($value['date']));
      
    $posts[$i]['message'] = $value['message'];
     $posts[$i]['message'] = $this->forum->bbcode_pre($posts[$i]['message']);
    $posts[$i]['message'] =$posts[$i]['message'];
   
    
    $i++;
  }
$this->template->posts = $posts;
$this->template->me = $user->login;
	$this->template->setView("topicView");
  }


}
?>
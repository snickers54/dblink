<?php
class	forum 
{
	private $class;
	
  public function	__construct($class)
  {
   foreach ($class AS $key => $value)
	    $this->$key = $value;
  }
	
 public function __get($key)
  {
    return (isset($this->class[$key])) ? $this->class[$key] : NULL;
  }

  public function __set($key, $val)
  {
    $this->class[$key] = $val;
  }

public function bbcode_pre($texte)
{
$texte = preg_replace('/\[code=(.*)\](.*)\[\/code\]/isU','<pre class="code" lang="$1">$2</pre>',$texte);

return $texte;	
}

public function bbcode($texte)
{
$texte = preg_replace('`\[b\](.+)\[/b\]`isU', '<strong>$1</strong>', $texte); 
	    $texte = preg_replace('`\[i\](.+)\[/i\]`isU', '<em>$1</em>', $texte);
	    $texte = preg_replace('`\[s\](.+)\[/s\]`isU', '<u>$1</u>', $texte);
	     $texte = preg_replace('`\[u\](.+)\[/u\]`isU', '<u>$1</u>', $texte);
	    $texte = preg_replace('#\[quote=(&\#039;|&quot;|"|\'|)(.*?)\\1\]#e', '"<small>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), \'$2\')." a ecrit:</small><blockquote><p>"', $texte);
	    $texte = preg_replace('#\[quote\]\s*#', '</p><blockquote>', $texte);
	    $texte = preg_replace('#\s*\[\/quote\]#S', '</p></blockquote>', $texte);
	    $texte = preg_replace('/\[url=(.*)\](.*)\[\/url\]/U','<a href="$1" target="_blank" class="label important">$2</a>',$texte);
	    $texte = preg_replace('`\[r\](.+)\[/r\]`isU', '<strike>$1</strike>', $texte);
	    $texte = preg_replace('/\[img\](.*)\[\/img\]/U','<img class="image_chat" src="$1" alt="image" />',$texte);
	    $texte = preg_replace('`\:\)`', '<img src="/public/images/chat/sourire.png" />', $texte);
	    $texte = str_replace(':P', '<img src="/public/images/chat/langue.png" />', $texte);
	    $texte = str_replace(':p', '<img src="/public/images/chat/langue.png" />', $texte);
	    $texte = str_replace('^^', '<img src="/public/images/chat/hihi.png" />', $texte);
	    $texte = str_replace(':D', '<img src="/public/images/chat/heureux.png" />', $texte);
	    $texte = str_replace(':d', '<img src="/public/images/chat/heureux.png" />', $texte);
	    $texte = str_replace(';)', '<img src="/public/images/chat/wink.png" />', $texte);
	    $texte = str_replace(':o', '<img src="/public/images/chat/huh.png" />', $texte);
	    $texte = str_replace(':O', '<img src="/public/images/chat/huh.png" />', $texte);
	    $texte = str_replace(':mdr:', '<img src="/public/images/chat/rire.gif" />', $texte);
	    $texte = str_replace(':euh:', '<img src="/public/images/chat/euh.gif" />', $texte);
	    $texte = str_replace(':triste:', '<img src="/public/images/chat/triste.png" />', $texte);
	    $texte = str_replace(":'(", '<img src="/public/images/chat/triste.png" />', $texte);
	    $texte = str_replace(':@', '<img src="/public/images/chat/colere.png" />', $texte);
	    $texte = str_replace(':colere:', '<img src="/public/images/chat/colere.png" />', $texte);
	    $texte = str_replace(':hein:', '<img src="/public/images/chat/hein.gif" />', $texte);
	    $texte = str_replace(':lala:', '<img src="/public/images/chat/siffle.png" />', $texte);
    	return $texte;
}


	public function addView($topic) {
		$this->db->query("update forum_topic set views = views + 1 where id = '".$topic."'");
	}

	public function	createCategorie($name, $order)
	{
		$this->db->query("insert into forum_categorie set name = '".$name."', `order` = '".$order."'");
	}
	
	public function	createForum($name, $id_cat, $right_create, $right_view, $right_write, $right_annonce, $moderators, $desc, $order)
	{
		$modo = serialize($moderators);
		$modo = mysql_real_escape_string($modo);
		$this->db->query("insert into forum_forum set id_cat = '".$id_cat."', name = '".$name."', `desc` = '".$desc."', right_create = '".$right_create."', 
		right_post = '".$right_write."', right_view = '".$right_view."', right_annonce = '".$right_annonce."', moderators = '".$modo."', 
		`order` = '".$order."'");
		return mysql_insert_id();
	}
	
	public function createTopic($forum_id, $titre, $message, $user, $genre)
	{
		$message = mysql_real_escape_string(htmlentities($message));
		$titre = mysql_real_escape_string(htmlentities($titre));
		$this->db->query("insert into forum_topic set id_forum = '".$forum_id."', name = '".$titre."', creator = '".$user."', genre = '".$genre."', id_first_post = 0, id_last_post = 0, views = 0, reponses = 0, `lock` = 0");
		$id_topic = mysql_insert_id();
		$id_post = $this->createPost($message, $user, $id_topic, 1);
		$this->db->query("update forum_topic set id_first_post = '".$id_post."', id_last_post = '".$id_post."' where id = '".$id_topic."'");
		$this->db->query("update forum_forum set nb_topics = nb_topics + 1 where id = '".$forum_id."'");
		return $id_topic;
	}
	
	public function createPost($message, $user, $id_topic, $first)
	{
	if ($first)
	{
		$this->db->query("insert into forum_posts set id_topic = '".$id_topic."', message = '".$message."', auteur = '".$user."', date = '".time()."'");
		$id_post = mysql_insert_id();
	}
		else
		{
			$this->db->query("insert into forum_posts set id_topic = '".$id_topic."', message = '".$message."', auteur = '".$user."', date = '".time()."'");
			$id_post = mysql_insert_id();
			$this->db->query("update forum_forum set nb_reponses = nb_reponses + 1, last_post = '".$id_post."' where id = (select id_forum from forum_topic where id = '".$id_topic."')");
			$this->db->query("update forum_topic set reponses = reponses + 1, id_last_post = '".$id_post."' where id = '".$id_topic."'");
		}
		return $id_post;
	}
	
	public function editPost($id, $message)
	{
		$this->db->query("update forum_posts set message = '".$message."' , `date` = '".time()."' where id ='".$id."'");
	}
	
	public function	deletePost($id)
	{
		$ret = $this->db->query("select id_last_post, id_first_post from forum_topic where id = (select id_topic from forum_posts where id = '".$id."')");
		if ($ret->row['id_first_post'] == $ret->row['id_last_post'])
		{
		$this->db->query("update forum_forum set nb_topics = nb_topics - 1 where id = (select id_forum from forum_topic where id = (select id_topic from forum_posts where id = '".$id."'))");
		$this->db->query("delete from forum_topic where id = (select id_topic from forum_posts where id = '".$id."')");
		$this->db->query("delete from forum_posts where id = '".$id."'");
		}
		else if ($ret->row['id_first_post'] == $id)
		{
		$reponse = $this->db->query("select reponses, id from forum_topic where id = (select id_topic from forum_posts where id = '".$id."')");
		$this->db->query("update forum_forum set nb_topics = nb_topics - 1, nb_reponses = (nb_reponses - '".$reponse->row['reponses']."') where id = (select id_forum from forum_topic where id = (select id_topic from forum_posts where id = '".$id."'))");
		$this->db->query("delete from forum_topic where id = (select id_topic from forum_posts where id = '".$id."')");
		$this->db->query("delete from forum_posts where id_topic = '".$reponse->row['id']."'");
		}
		else
		{
		$this->db->query("update forum_topic set reponses = reponses - 1 where id = (select id_topic from forum_posts where id = '".$id."')");
		$forum_topic = $this->db->query("select id_topic from forum_posts where id = '".$id."'");
		$this->db->query("update forum_forum set nb_reponses = nb_reponses - 1 where id = (select id_forum from forum_topic where id = (select id_topic from forum_posts where id = '".$id."'))");
		$this->db->query("delete from forum_posts where id = '".$id."'");
		$this->db->query("update forum_topic set id_last_post = (select id from forum_posts where id_topic = '".$forum_topic->row['id_topic']."' order by id desc limit 0,1)");
		}
	}
	
	public function getConfig()
	{
	  $config = $this->db->query("select * from forum_config");
	  return $config;
	}

	public function getConfigFromKey($key)
	{
	  $config = $this->db->query("select * from forum_config where cle ='".$key."'");
	 
	  return $config->row['valeur'];
	}

	public function deleteTopic($id)
	{
		$id = $this->db->query("select id_first_post from forum_topic where id = '".$id."'");
		$this->deletePost($id->row['id_first_post']);
	}
	
	public function deleteForum($id)
	{
		$topics = $this->db->query("select id from forum_topic where id_forum = '".$id."'");
		foreach ($topics->rows as $value)
		$this->deleteTopic($value['id']);
		$this->db->query("delete from forum_forum where id = '".$id."'");
	}

	public function deleteCategorie($id)
	{
		$forums = $this->db->query("select id from forum_forum where id_cat = '".$id."'");
		foreach ($forums->rows as $value)
			$this->deleteForum($value['id']);
		$this->db->query("delete from forum_categorie where id = '".$id."'");
	}
	
	public function	reorderCategorie($array)
	{
		foreach ($array as $value)
		{
			$this->db->query("update forum_categorie set `order` = '".$value['pos']."' where id = '".$value['id']."'");
		}
	}
	
	public function	updateGenre($id, $genre)
	{
		$this->db->query("update forum_topic set genre = '".$genre."' where id = '".$id."'");
	}
	
	public function	reorderForum($array)
	{
		foreach ($array as $value)
		{
			$this->db->query("update forum_forum set `order` = '".$value['pos']."' where id = '".$value['id']."'");
		}
	}
	
	public function	lockTopic($id, $value=1)
	{
		$this->db->query("update forum_topic set lock = '".$value."' where id = '".$id."'");
	}
	
	public function	lockTopicArray($array_id, $value = 1)
	{
		$this->db->query("update `forum_topic` set `lock` = '".$value."' where id in (".implode(',', $array_id).")") or die(mysql_error());
	}

	public function moveTopic($id, $to_forum_id)
	{
		$topic = $this->db->query("select * from forum_topic where id = '".$id."'");
		$this->db->query("update forum_forum set nb_topics = nb_topics - 1 , nb_reponses = nb_reponses - '".$topic->row['reponses']."' where id = '".$topic->row['id_forum']."'");
		$this->db->query("update forum_forum set nb_topics = nb_topics + 1, nb_reponses = nb_reponses + '".$topic->row['reponses']."' where id = '".$to_forum_id."'");
		$this->db->query("update forum_topic set id_forum = '".$to_forum_id."' where id = '".$id."'");
	}
	
	public function getEverything()
	{
		$get = $this->db->query("select forum_categorie.id as 'id_categorie', forum_categorie.name as 'name_cat', forum_categorie.order as 'cat_order' , forum_forum.id as 'id_forum1', forum_forum.id_cat, forum_forum.name as 'name_forum', forum_forum.desc,
		forum_forum.right_view, forum_forum.moderators, forum_forum.last_post, forum_forum.order as 'forum_order', forum_forum.nb_topics, forum_forum.nb_reponses, forum_topic.id as 'id_topic1', forum_topic.id_forum as 'id_forum2', forum_topic.id_last_post,
		forum_posts.id as 'id_post1' from forum_categorie
		left join forum_forum on forum_categorie.id = forum_forum.id_cat
		left join forum_posts on forum_forum.last_post = forum_posts.id
		left join forum_topic on forum_posts.id_topic = forum_topic.id
		order by forum_categorie.order, forum_forum.order asc");
		return $get;
	}

	public function getCat()
	{
		$ret = $this->db->query("select * from forum_categorie");
		return $ret;
	}
	
	public function getCatMaxOrder()
	{
		$ret = $this->db->query("select MAX(`order`) as 'max' from forum_categorie");
		return $ret;
	}

	public function getForumMaxOrder($id_cat)
	{
		$ret = $this->db->query("select MAX(`order`) as `max` from forum_forum where `id_cat` = '".$id_cat."'");
		return $ret;
	}
	public function getForumsAndCat()
	{
		$ret = $this->db->query("select forum_categorie.id as 'cat_id', forum_categorie.name as 'name_cat', forum_forum.name as 'name_forum', forum_forum.id as 'forum_id'
			from forum_forum left join forum_categorie on forum_forum.id_cat = forum_categorie.id order by forum_categorie.id asc");
		return $ret;
	}
	public function	forumExist($id)
	{
		$test = $this->db->query("select * from forum_forum where id = '".$id."'");
		if ($test->count == 0)
		return false;
		return true;
	}
	
	public function	topicExist($id)
	{
		$test = $this->db->query("select id from forum_topic where id = '".$id."'");
		if ($test->count == 0)
		return false;
		return true;
	}
	
	public function	postExist($id)
	{
		$test = $this->db->query("select id from forum_posts where id = '".$id."'");
		if ($test->count == 0)
		return false;
		return true;
	}
	
	
	public function	getTopicsFromForum($id_forum)
	{
		$ret = $this->db->query("select forum_topic.id as 'id', forum_topic.name as 'name', forum_topic.creator, forum_topic.genre, forum_topic.id_last_post,
		forum_topic.id_first_post, forum_topic.lock as 'lock', forum_topic.views, forum_topic.reponses, forum_topic.genre, forum_posts.id as 'id_post', 
		forum_posts.auteur, forum_posts.date from forum_topic left join forum_posts on forum_topic.id_last_post = forum_posts.id where id_forum = '".$id_forum."' order by forum_topic.genre asc, forum_posts.date desc");
		return $ret;
	}
	
	public function getPostsFromTopic($id_topic)
	{
	$ret = $this->db->query("select p.*, u.user_id from forum_posts p left join user u on p.auteur = u.login where id_topic = '".$id_topic."'");
	return $ret;
	}
	
	public function isLocked($id_topic)
	{
		$ret = $this->db->query("select `lock` from forum_topic where id = '".$id_topic."'");
		return ($ret->row['lock'] == 1) ? true : false;
	}

	public function amIOwner($id_post, $login)
	{
	$ret = $this->db->query("select auteur from forum_posts where id = '".$id_post."'");
	if ($login == $ret->row['auteur'])
	return true;
	return false;
	}
	public function getPost($id)
	{
	$ret = $this->db->query("select * from forum_posts where id = '".$id."'");
	return $ret;
	}
	
	public function getForumById($id)
	{
	$ret = $this->db->query("select * from forum_forum where id = '".$id."'");
	return $ret;
	}
	
	public function getArianeFromPost($id_post)
	{
	 $ret = $this->db->query("select forum_posts.id_topic as 'topic_id', forum_topic.name as 'topic_name', forum_topic.id_forum as 'forum_id', forum_forum.name as 'forum_name'
	  from forum_forum left join forum_topic on forum_forum.id = forum_topic.id_forum 
	  left join forum_posts on forum_topic.id = forum_posts.id_topic where forum_posts.id = '".$id_post."'");
	  return $ret;
	}
	public function getArianeFromTopic($id_post)
	{
	 $ret = $this->db->query("select forum_topic.id as 'topic_id', forum_topic.name as 'topic_name', forum_topic.id_forum as 'forum_id', forum_forum.name as 'forum_name', forum_forum.right_view as 'right_view', forum_forum.right_post as 'right_post'
	  from forum_forum left join forum_topic on forum_forum.id = forum_topic.id_forum 
	   where forum_topic.id = '".$id_post."'");
	  return $ret;
	}

	public function getTopicById($id)
	{
		$ret = $this->db->query("select * from forum_topic where id = '".$id."'");
		return $ret;
	}

	public function getForumByName($name)
	{
		$ret = $this->db->query("select * from forum_forum where name = '".$name."'");
		return $ret;
	}

	public function getPostById($id)
	{
		$ret = $this->db->query("select * from forum_posts where id = '".$id."'");
		return $ret->row;
	}
}
?>
global = new Array();
_race = 0;

$(document).ready(function(){
	poll();
  msgListener();
	$("#chat_send").live('keydown', function(event) {               
        var obj = $(this);
        if (obj.val().length <= 0)

          return ;
	    if (event.keyCode == 13 && !($.browser.msie && $.browser.version < 8)) {
	    	$f.exec("chat:chatSend");
	    }
    });
});

function msgListener()
{
  if (global.length > 0)
    $f.exec("chat:send");
  setTimeout(msgListener, 2000);
}

function poll()
{
    $.get('/user/poll', {}, function(data){
       	dispatch(data);
        addSuccess(data);
        if (data._error_ == undefined)
          setTimeout(poll, 2000);
    }, "json");
};

function notifications_dispatch(data)
{
  for (var i in data)
  {
    notif = data[i];
    addSuccess(notif);
    addError(notif);

    if (notif.type == "energie")
      reloadContent();
  }
}

function chooseRace(data)
{
  if (data != undefined && _race == 0)
  {
    race = 1;
    $("#modal_race").modal('show');
  }
}

function dispatch(data)
{
  my = data.my;

  chooseRace(data.race);
  addAchievement(data);
  chat_del(data.chat_del);
	chat_left(data.chat);
	chat_center(data.chat);
  rapports(data.rapport);
  deplacements(data.deplacements);
  notifications_dispatch(data.notifications);
  mp(data.mp);
  friends_connected(data.friends);
}

function deplacements(data)
{
  if (data != undefined)
  {
      incremental_shortcuts($("#notif_move").find("sub"), data.length);
    if (!$("#notif_move").hasClass("mail-warn"))
      $("#notif_move").addClass("mail-warn");
  }
}

function rapports(data)
{
  if (data != undefined)
  {
    incremental_shortcuts($("#notif_rapports").find("sub"), data.length);
    if (!$("#notif_rapports").hasClass("mail-warn"))
      $("#notif_rapports").addClass("mail-warn");
  }
}

function friends_connected(data)
{
  if (data == undefined)
    return ;
  var elem = $("<li class='list_connected'><a href='' class='label label-inverse'><span class='badge pull-right'></span> <span class='connected_login'></span></a></li>");
  $("#wrapper_connected").find('li.list_connected').remove();
  if (data.length > 0)
  {
    for (var i in data)
    {
      var clone = elem.clone();
      clone.find('.badge').addClass(data[i].ping_color);
      clone.find('a').attr('href', '#');
      clone.find('.connected_login').html(data[i].login);
      $("#wrapper_connected").find('li:first').after(clone);
    }
  }
}

function incremental_shortcuts(list, plus)
{
  var nb = parseInt(list.attr("nb")) + plus;
  list.html("+" + nb);
  list.attr("nb", nb);
}

function chat_left(data)
{
  if (data != undefined)
    if ($("#mask").hasClass("off"))
    {
      incremental_shortcuts($("#notif_chat").find("sub"), data.length);
      if (!$("#notif_chat").hasClass("mail-warn"))
        $("#notif_chat").addClass("mail-warn");
    }
}

function mp(data)
{
  if (data == undefined)
    return ;
  if (!$("#notif_mails").hasClass("mail-warn"))
    $("#notif_mails").addClass("mail-warn");
  incremental_shortcuts($("#notif_mails").find("sub"), data.length);
}

function chat_del(data)
{
  if (data == undefined)
    return ;
  for (var i in data)
  {
    if ($("#message_"+data[i].msg_id))
    $("#message_"+data[i].msg_id).fadeOut('slow', function(){
      $(this).remove();
    });
  }
}

function chat_center(data)
{
	if (data == undefined)
		return ;
  var elem2 = $('<div class="chat_msg"><hr /><div class="row-fluid">' +
          '<div class="span12">'+
            '<blockquote class="chat_content"></blockquote>'+
            '<div class="off chat_rawcontent"></div>'+
            '</div></div></div>');
	var elem = $('<div class="chat_msg"><hr /><div class="row-fluid">' +
				 	'<div class="span2">'+
						'<a href="#" class="chat_photolink"><img src="" class="avatar_medium img-polaroid chat_avatar" /></a>'+
					'</div>'+
					'<div class="span10">'+
						'<ul class="nav nav-pills">'+
							'<li><a class="label label-inverse chat_userlink" href="#"><i class="icon-user icon-white"></i><span class="chat_login"></span></a></li>'+
							'<li><a data-placement="bottom" class="tip label label-inverse" data-original-title="Grade" href="#"><i class="icon-chevron-up icon-white"></i><span class="chat_grade"></span></a></li>'+
							'<li><a data-placement="bottom" class="tip label label-inverse" data-original-title="Alliances" href="#"><i class="icon-flag icon-white"></i> <span class="chat_alliance"></span></a></li>'+
						'</ul>'+
            '<span data-placement="bottom" data-original-title="quote" class="pull-right cursor tip" click="chat:quote"><i class="icon-comment icon-white"></i></span>' +
            '<blockquote class="chat_content"></blockquote>'+
            '<div class="off chat_rawcontent"></div>'+
            '<i class="date"></i></div></div></div>');
	if (data.length > 0)
	{
		$(".waiting_chat").remove();
		var list = $("#wrapper_chat .well .chat_msg:first");
		for (var i in data)
		{
      if (data[i].user_id != 0)
			 var clone = elem.clone();
      else
        clone = elem2.clone();
			clone.find('.chat_login').html(data[i].login);
			clone.find('.chat_grade').html(data[i].grade);
			if (data[i].ally_nom == undefined || data[i].ally_nom == null)
				clone.find('.chat_alliance').parent().parent().remove();
			else
				clone.find('.chat_alliance').html(data[i].ally_nom);
			clone.find('.chat_avatar').attr("src", data[i].avatar);
			clone.find('.chat_userlink').attr("href", "/user/index?user_id="+data[i].user_id);
			clone.find('.chat_photolink').attr("href", "/user/index?user_id="+data[i].user_id);
      var msg = data[i].msg.replace("\n", "<br />");
			clone.find('.chat_content').html(msg);
      clone.find('.chat_rawcontent').html(data[i].msg_wbbcode);
      clone.find(".date").attr("time", data[i].timestamp);
    if (my.user_id == data[i].user_id || my.isAdmin == 1 || my.isModo == 1)
        clone.find(".span10").prepend('<button type="button" class="close" data-dismiss="modal" aria-hidden="true" click="chat:del" msg_id="'+data[i].id+'">×</button>');
      clone.attr("id", "message_"+data[i].id);
			list.before(clone);
		}
	  //blink_chat();
		general();
	}
}

function insert_link(name)
{
   msgfield = $("[name="+name+"]")[0];
   // IE support
   var link = prompt("adresse du lien : ");
   var text = prompt("Text du lien : ");

   var open = "[url=" + link + "]";
   var close = text + "[/url]";
   var startPos = msgfield.selectionStart;
   var endPos = msgfield.selectionEnd;
   var old_top = msgfield.scrollTop;
   msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos) + close + msgfield.value.substring(endPos, msgfield.value.length);
   msgfield.selectionStart = msgfield.selectionEnd = endPos + open.length + close.length;
   msgfield.scrollTop = old_top;
   msgfield.focus();
 }

 function insert_text(open, close, name)
 {
  msgfield = (document.all) ? document.all.req_message : ((document.getElementById('afocus') != null) ? (document.getElementById('afocus').req_message) : (document.getElementsByName(name)[0]));

   // IE support
   if (document.selection && document.selection.createRange)
   {
     msgfield.focus();
     sel = document.selection.createRange();
     sel.text = open + sel.text + close;
     msgfield.focus();
   }

   // Moz support
   else if (msgfield.selectionStart || msgfield.selectionStart == '0')
   {
     var startPos = msgfield.selectionStart;
     var endPos = msgfield.selectionEnd;
     var old_top = msgfield.scrollTop;
     msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos) + close + msgfield.value.substring(endPos, msgfield.value.length);
     msgfield.selectionStart = msgfield.selectionEnd = endPos + open.length + close.length;
     msgfield.scrollTop = old_top;
     msgfield.focus();
   }

   // Fallback support for other browsers
   else
   {
     msgfield.value += open + close;
     msgfield.focus();
   }
 }

function exec_quote(data)
{
  msgfield = $("[name="+data.name+"]")[0];
  open = '[quote=' + data.author + ']' + data.message;
  close = "[/quote]";

  if (document.selection && document.selection.createRange)
  {
    msgfield.focus();
    sel = document.selection.createRange();
    sel.text = open + sel.text + close;
    msgfield.focus();
  }

   // Moz support
   else if (msgfield.selectionStart || msgfield.selectionStart == '0')
   {
    var startPos = msgfield.selectionStart;
    var endPos = msgfield.selectionEnd;
    var old_top = msgfield.scrollTop;
    msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos) + close + msgfield.value.substring(endPos, msgfield.value.length);
    msgfield.selectionStart = msgfield.selectionEnd = endPos + open.length + close.length;
    msgfield.scrollTop = old_top;
    msgfield.focus();
  }
   // Fallback support for other browsers
   else
   {
    msgfield.value += open + close;
    msgfield.focus();
  }
}

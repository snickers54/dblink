$f.chat = {
	toggleChat:function(e)
	{
		$("#wrapper_chat, #wrapper_connected").fadeToggle();
		$("#mask").toggleClass('off');
		$("#notif_chat").find('sub').attr('nb', 0);
		$("#notif_chat").find("sub").html("+0");
		$("#notif_chat").removeClass("mail-warn");
	},
	chatSend:function(e)
	{
		var text = $("#chat_send").val();
		$("#chat_send").val("");
		$("#wrapper_chat .well .row-fluid:first").before("<div class='row-fluid waiting_chat'><div class='span12'><img src='/public/images/loader.gif' /><br /><br />Loading</div></div><hr class='waiting_chat'/>");
		global.push({texte: text, channel: 'chat'});
	},
	send:function(e)
	{
		$.post('/user/send', {msg: global}, function(data){
			global = [];
		}, "json");
	},
	quote:function(e){
		var elem = e.parents('.row-fluid');
		var author = elem.find('.chat_login').html();
		var msg = elem.find('.chat_rawcontent').html();
		exec_quote({author: author, message:msg, name:'send'});
	},
	del:function(e)
	{
		var msg_id = e.attr("msg_id");
		$.post('/user/delete', {id:msg_id, channel:"chat"}, function(data){
			if (!addError(data))
				$("#message_"+msg_id).fadeOut('slow', function(){
					$(this).remove();
				});
		}, "json");
	}
};
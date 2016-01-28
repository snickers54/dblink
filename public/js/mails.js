$f.mails = {
	childrenMail:function(e){
		var id_mail = e.parent().attr("mail_id");
		reloadContent('/user/mailAction?id='+id_mail);
	},
	listMail:function(e){
		reloadContent('/user/mailsAction');
	},
	// #TODO ameliorer pour faire une seule requete ajax
	deleteAll:function(e){
		$("#list_mails").find("input[type=checkbox]:checked").each(function(){
			var input = $(this);
			$.post("/user/mailManage", {type: "delete", id:$(this).val()}, function(){
				input.parent().parent().remove();
			}, "json");
		});
	},
	checkAll:function(e)
	{
		$("#list_mails").find("input[type=checkbox]").each(function(){
			if ($(this).is(":checked"))
				$(this).attr("checked", false);
			else
				$(this).attr("checked", true);
		});
	},
	delete:function(e){
		var id = e.parent().attr("id_message");
		$.post('/user/mailManage', {type: "delete", id:id}, function(data){
			addError(data);
			addSuccess(data);
			$f.exec('mails:listMail');
		}, "json");
	},
	answer:function(e)
	{
		var id = e.parent().attr("id_message");
		$.get('/user/mailAnswerAction', {id_parent: id}, function(data){
			$("#mail_new").remove();
			if (!addError(data))
			{
				$("#view_content").append(data._html_);
				goBot();
			}
		}, "json");
	},
	new:function(e){
		reloadContent('/user/mailNewAction');
	},
	send:function(e){
		var params = $f.getform("mail_new");
		$.post('/user/mailSend', params, function(data){
			addError(data);
			if (addSuccess(data))
				$f.exec('mails:listMail');
		},"json");
	},
	delReceiver:function(e)
	{
		e.parent().remove();
	},
	addReceiver:function(e){
		var user_id = e.attr("user_id");
		var login = e.html().toUpperCase();
		var completion_list = e.parents('.controls').parent().find('.completion_list ul');
		$("#mail_receiver").val("");
		completion_list.html("");
		completion_list.addClass('off');

		var receiver = $("<span class='label label-inverse'><span class='badge cursor' click='mails:delReceiver'>x</span> "+login+"<input type='hidden' name='author["+user_id+"]' value='"+user_id+"' /></span>");
		$("#mail_new").find('.receivers').append(receiver);
	},
	completion:function(e){
		var completion_list = e.parents('.controls').parent().find('.completion_list ul');
		var letter = e.val();
		var li = $('<li><a href="#" user_id="" class="label label-inverse" click="mails:addReceiver"></a></li>');
		var fill = function(data){
			completion_list.removeClass('off');
			completion_list.html("");
			if (letter.length > 0)
			for (var i in data)
			{
				var regex = new RegExp('^'+letter, 'i');
				var login = data[i].login;
				if (login.match(regex))
				{
					var clone = li.clone();
					clone.find('a').attr("user_id", data[i].user_id);
					clone.find('a').html(data[i].login.toUpperCase());
					completion_list.append(clone);
				}
			}
		};
		if (Completion_User.length == 0)
			$.get('/user/completion', {}, function(data){
				Completion_User = data.users;
				fill(data.users);
			}, "json");
		else
			fill(Completion_User);
	}
};
$f.stats = {
	changePage:function(e){
		var page = e.val();
		window.location = "/user/stats?page="+page;
	},
	addComparer:function(e){
		var user_id = e.attr("user_id");
		var login = e.html().toUpperCase();
		var completion_list = e.parents('.controls').parent().find('.completion_list ul');
		$("#stats_comparer").val("");
		completion_list.html("");
		completion_list.addClass('off');

		var receiver = $("<span class='label label-inverse'><span class='badge cursor' click='stats:delComparer'>x</span> "+login+"<input type='hidden' name='comparer["+user_id+"]' value='"+user_id+"' class='comparer'/></span>");
		$("#comparer").find('.receivers').append(receiver);
		$f.exec("stats:compare");
	},
	delComparer:function(e){
		e.parent().remove();
		$f.exec("stats:compare");
	},
	completion:function(e){
		var completion_list = e.parents('.controls').parent().find('.completion_list ul');
		var letter = e.val();
		var li = $('<li><a href="#" user_id="" class="label label-inverse" click="stats:addComparer"></a></li>');
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
	},
	compare:function(e){
		var form = $f.getform("form_comparer");
		if ($(".comparer").length == 0)
		{
			var page = $("select[name=page_stats]").val();
			reloadContent("/user/statsAction?page="+page);
			return ;
		}
		$.get('/user/statsCompare', form, function(data){
			if (!addError(data))
			{
				var dom = $("#view_content .row-fluid .span8 .well");
				dom.html(data._html_);
			}
		}, "json");
	}
}
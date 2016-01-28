$f.alliance = {

	updateAlly:function(e) {
		
		var item = e.attr("item");
		var desc = $("#" + item).val();
		var obj = {};
		obj.value = desc;
		obj.item = item;
		console.log(item);
		console.log(desc);
		$.ajax({
		url: "/alliance/updateItem",
		type: "POST",
		dataType: "JSON",
		data: obj,
		success: function(data) {
		addSuccess(data);	
		}
	});
	},

	closeNews:function(e) {
		$(".news dt").next().not($(this).next()).slideUp();
		$(this).next().slideToggle();
	},

	postNews:function(e){
		var title = $("#titleNews").val();
		var content = $("#contentNews").val();
		var obj = {};
		obj.title = title;
		obj.content = content;
			$.ajax({
		url: "/alliance/postNews",
		type: "POST",
		dataType: "JSON",
		data: obj,
		success: function(data) {
		addSuccess(data);	
		}
	});
	},

	getNews:function(e) {
		var id = $("#getNews").val();
		var obj = {};
		obj.id = id;
				$.ajax({
		url: "/alliance/postNews",
		type: "POST",
		dataType: "JSON",
		data: obj,
		success: function(data) {
			$("#modifyTitleNews").val(data.title);
			$("#modifyContentNews").val(data.content);	
		}
	});
	},

		modifyNewsSend:function(e){
		var title = $("#modifyNewsTitle").val();
		var content = $("#modifyNewsContent").val();
		var id = $("#modifyNewsID").val();
		var obj = {};
		obj.id = id;
		obj.title = title;
		obj.content = content;
			$.ajax({
		url: "/alliance/editNews",
		type: "POST",
		dataType: "JSON",
		data: obj,
		success: function(data) {
		addSuccess(data);
		$("[id_news='"+id+"']").attr("title", title);
		$("[id_news="+id+"]").attr("content", content);
		}
	});
	},

	modifyNews:function(e){
		var title= e.attr("title");
		var id = e.attr("id_news");
		var content = e.attr("content");

		window.location = "#modifyNewsTitle";
		$("#modifyNewsTitle").val(title);
		$("#modifyNewsID").val(id);
		$("#modifyNewsContent").val(content);
	},

	modifyRank:function(e){
		var id_user = e.attr('user');
		var id_grade = $("[name=gradeSelect" + id_user + "]").val();
		console.log(id_user);
		var obj = {};
		obj.id = id_user;
		obj.id_grade = id_grade;
		obj.login = e.attr('userName');
		obj.grade = $("[name=gradeSelect" + id_user + "]  option:selected").text();
		$.ajax({
			url: "/alliance/modifyRank",
			type: "POST",
			dataType: "JSON",
			data: obj,
			success: function(data) {
				addSuccess(data);
			}
		});
	},

	acceptMember:function(e){
		var id = e.attr("id_user");
		var obj = {};
		obj.id = id;
		obj.login = e.attr("userName");
		$.ajax({
			url: "/alliance/acceptMember",
			type: "POST",
			dataType: "JSON",
			data: obj,
			success: function(data) {
				addSuccess(data);
				e.parent().parent().fadeOut();
			}
		});
	},

	rejectMember:function(e){
		var id = e.attr("id_user");
		var obj = {};
		obj.id = id;
		$.ajax({
			url: "/alliance/rejectMember",
			type: "POST",
			dataType: "JSON",
			data: obj,
			success: function(data) {
				addSuccess(data);
				e.parent().parent().fadeOut();
			}
		});
	},

	deleteNews:function(e){
		var id = e.attr("id_news");
		var c = confirm("êtes vous sur de vouloir supprimé cette news");
		if (c == true)
		{
			var i = e.parent().parent();
			i.hide();
			
			var obj = {};
			obj.id = id;
				$.ajax({
					url: "/alliance/deleteNews",
					type: "POST",
					dataType: "JSON",
					data: obj,
					success: function(data) {
					addSuccess(data);
					}
				});
		}
	}
};

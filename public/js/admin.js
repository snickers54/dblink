$f.admin = {
	loadBuilding:function(e)
	{
		var id = e.attr("building_id");
		$("#buildings_list").find('li').removeClass("active");
		e.addClass("active");
		$(".building").hide();
		$("#building_new").hide();
		$("#building_"+id).show();
	},
	loadNew:function(e){
		$("#buildings_list").find('li').removeClass("active");
		$(".building").hide();
		$("#building_new").show();
	},
	deleteBuilding:function(e){
		var id = e.attr("building_id");
		$.post('/admin/deleteBuilding', {id:id}, function(data){
			if (addSuccess(data))
				reloadContent("/admin/buildingsAction");	
			addError(data);
		}, "json");		
	},
	modifBuilding:function(e)
	{
		var id = e.attr("building_id");
		var form = $f.getform("building_form_"+id);
		$.post('/admin/modifBuilding', form, function(data){
			addSuccess(data);
			addError(data);
		}, "json");
	},
	createBuilding:function(e)
	{
		var form = $f.getform("building_new");
		$.post('/admin/createBuilding', form, function(data){
			if (addSuccess(data))
				reloadContent("/admin/buildingsAction");	
			addError(data);
		}, "json");
	},
	loadUser:function(e)
	{
		var id = e.attr("user_id");
		$.get('/admin/getUser', {id:id}, function(data){
			addSuccess(data);
			addError(data);
			if (data._html_)
				$("#admin_content").html(data._html_);
		}, "json");
	},
	deleteUser:function(e)
	{
		var id = e.attr("user_id");
		$.post('/admin/deleteUser', {id:id}, function(data){
			addSuccess(data);
			addError(data);
			$("#admin_content").html("");
		}, "json");
	},
	activateUser:function(e){
		var id = e.attr("user_id");
		$.post('/admin/activateUser', {id:id}, function(data){
			addSuccess(data);
			addError(data);
			e.remove();
		}, "json");
	},
	modifUser:function(e){
		var form = $f.sendform("adminUser");
	},
	loadSuccess:function(e)
	{
		var id = e.attr("success_id");
		$("#success_list").find('li').removeClass("active");
		e.addClass("active");
		$(".success").hide();
		$("#success_new").hide();
		$("#success_"+id).show();
	},
	newSuccess:function(e){
		$("#success_list").find('li').removeClass("active");
		$(".success").hide();
		$("#success_new").show();
	},
	createSuccess:function(e)
	{
		var form = $f.getform("success_new");
		$.post('/admin/createSuccess', form, function(data){
			if (addSuccess(data))
				reloadContent("/admin/successAction");	
			addError(data);
		}, "json");
	},
	modifSuccess:function(e){
		var id = e.attr("success_id");
		var form = $f.getform("success_form_"+id);
		$.post('/admin/modifSuccess', form, function(data){
			addSuccess(data);
			addError(data);
		}, "json");
	},
	deleteSuccess:function(e){
		var id = e.attr("success_id");
		$.post('/admin/deleteSuccess', {id:id}, function(data){
			if (addSuccess(data))
				reloadContent("/admin/successAction");	
			addError(data);
		}, "json");	
	},

	loadMission:function(e)
	{
		var id = e.attr("mission_id");
		$("#mission_list").find('li').removeClass("active");
		e.addClass("active");
		$(".mission").hide();
		$("#mission_new").hide();
		$("#mission_"+id).show();
	},
	newMission:function(e){
		$("#mission_list").find('li').removeClass("active");
		$(".mission").hide();
		$("#mission_new").show();
	},
	createMission:function(e)
	{
		var form = $f.getform("mission_new");
		$.post('/admin/createMission', form, function(data){
			if (addSuccess(data))
				reloadContent("/admin/missionAction");	
			addError(data);
		}, "json");
	},
	modifMission:function(e){
		var id = e.attr("mission_id");
		var form = $f.getform("mission_form_"+id);
		$.post('/admin/modifMission', form, function(data){
			addSuccess(data);
			addError(data);
		}, "json");
	},
	deleteMission:function(e){
		var id = e.attr("mission_id");
		$.post('/admin/deleteMission', {id:id}, function(data){
			if (addSuccess(data))
				reloadContent("/admin/missionAction");	
			addError(data);
		}, "json");	
	},
	
	loadTerrains:function(e)
	{
		var id = e.attr("terrains_id");
		$("#terrains_list").find('li').removeClass("active");
		e.addClass("active");
		$(".terrains").hide();
		$("#terrains_new").hide();
		$("#terrains_"+id).show();
	},
	newTerrains:function(e){
		$("#terrains_list").find('li').removeClass("active");
		$(".terrains").hide();
		$("#terrains_new").show();
	},
	createTerrains:function(e)
	{
		var form = $f.getform("terrains_new");
		$.post('/admin/createTerrains', form, function(data){
			if (addSuccess(data))
				reloadContent("/admin/terrainsAction");	
			addError(data);
		}, "json");
	},
	modifTerrains:function(e){
		var id = e.attr("terrains_id");
		var form = $f.getform("terrains_form_"+id);
		$.post('/admin/modifTerrains', form, function(data){
			addSuccess(data);
			addError(data);
		}, "json");
	},
	deleteTerrains:function(e){
		var id = e.attr("terrains_id");
		$.post('/admin/deleteTerrains', {id:id}, function(data){
			if (addSuccess(data))
				reloadContent("/admin/terrainsAction");	
			addError(data);
		}, "json");	
	}

};
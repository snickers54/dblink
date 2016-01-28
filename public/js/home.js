$f.home = {

	changeRace:function(e){
		var race = $("#select_race").val();
		$.post('/user/changeRace', {race:race}, function(data){
			addError(data);
			addSuccess(data);
			$("#modal_race").modal('hide');
		}, "json")
	},
	changeProductivity:function(e){
		var prodType = e.attr("name");
		var prodValue = parseInt(e.val());
		var prodMax = parseInt(e.attr("max_prod"));

		var new_prod = Math.round(prodMax * prodValue / 100);
		$("#"+prodType).html(new_prod);
		$.post('/board/changeProductivity', {type:prodType, prod:prodValue}, function(data){
			addError(data);
		}, "json");
	},
	changePlanet:function(e)
	{
		var planet_id = $(e).attr("planet_id");
		$.get('/user/changePlanet', {planet_id: planet_id}, function(data){
			if (!addError(data))
			{
		        $("#block_planet_left").html(data._html_);
		        refreshBlockLeft()
				reloadContent();		        
		    }
		}, "json");
	},
	hideResources:function(e)
	{
		var value = e.val();
		if (value == "resource_ships")
			$("#form_resources_move").hide();
		else
			$("#form_resources_move").show();
	},
	changeTimeColonisation:function(e)
	{
		var value = e.val();
		if (value == "colonisation_normal")
		{
			$("#time_colonisation_normal").removeClass("off");
			$("#time_colonisation_technologie").addClass("off");
		}
		else
		{
			$("#time_colonisation_normal").addClass("off");
			$("#time_colonisation_technologie").removeClass("off");
		}
	},
	buyAvatarPlanet:function(e)
	{
		var image = $(e).attr("image");
		$.post('/user/changeAvatarPlanet', {image:image, buy:"yes"}, function(data){
			$("#planetAvatar").modal('hide');
			refreshBlockLeft()
			if (!addError(data))
				$("#planet_avatar").attr("src", "/public/images/planete/"+image);
			addSuccess(data);
		}, "json");
	},
	changeTimeRessources:function(e)
	{
		var time = e.val();
		setCookie("ressources_time", time, 360);
	},
	changeAvatarPlanet:function(e)
	{
		var image = $(e).attr("image");
		$.post('/user/changeAvatarPlanet', {image:image}, function(data){
			$("#planet_avatar").attr("src", "/public/images/planete/"+image);
			$("#planetAvatar").modal('hide');
			addError(data);
			addSuccess(data);
		}, "json");
	},
	editPlanet:function(e)
	{
		var name = $("#editPlanet").find("input[name=nom_planet]").val();
		var notes = $("#editPlanet").find("textarea[name=note_planet]").val();

		$.post('/user/editPlanet', {name: name, notes:notes}, function(data){
			$("#editPlanet").modal('hide');
			refreshBlockLeft()
			addError(data);
			addSuccess(data);
		}, "json");
	},
	changeLogin:function(e){
		var login = $("#editUser").find("input[name=pseudo]").val();
		$.post('/user/changeLogin', {login:login}, function(data){
			$("#editUser").modal('hide');
			addError(data);
			addSuccess(data);
			refreshBlockLeft();
		});
	},
	editUser:function(e){
		var prenom = $("#editUser").find('input[name=prenom]').val();
		var nom = $("#editUser").find('input[name=nom]').val();
		var password = $("#editUser").find('input[name=password]').val();
		var password2 = $("#editUser").find('input[name=password2]').val();
		var background = $("#editUser").find('textarea[name=background]').val();
		var obj = {nom:nom, prenom:prenom, background:background};

		if (password.length > 0)
		{
			obj.password = password;
			obj.password2 = password2;
		}
		$.post('/user/editUser', obj, function(data){
			$("#editUser").modal('hide');
			addError(data);
			addSuccess(data);
			refreshBlockLeft();
		}, "json");
	}
};
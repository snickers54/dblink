$(document).ready(function(){
	activeOnglet();
});

function activeOnglet()
{
	var onglet = getCookie("empire_onglet");
	if (onglet)
	{
		$("#vaisseaux").removeClass("active");
		$("#vaisseaux_li").removeClass("active");
		$(onglet).addClass("active");
		$(onglet + "_li").addClass("active");
	}	
};

$f.board = {
	delRapport:function(e)
	{
		var id_rapport = e.attr("rapport_id");
		$.post('/board/delRapport', {id_rapport:id_rapport}, function(data){
			$("#rapport_"+id_rapport).remove();
			$("#rapport_content").html("");
		}, "json");
	},
	launchScan:function(e){
		$.post('/move/launchScan', {}, function(data){
			if (addSuccess(data))
				e.remove();
			addError(data);
		}, "json");
	},
	exportRapport:function(e)
	{
		var id_rapport = e.attr("rapport_id");
		$("#export_rapport").show();
		$("#export_rapport input").val("[rapport="+id_rapport+"]");
	},
	rapportEmpire:function(e){
		var civils = e.parent().parent().find('input[name=civils]').attr("checked");
		var defenses = e.parent().parent().find('input[name=defenses]').attr("checked");
		var vaisseaux = e.parent().parent().find('input[name=vaisseaux]').attr("checked");

		$.get('/board/createReportEmpire', {civils:civils, defenses:defenses, vaisseaux:vaisseaux}, function(data){
			$("#generate_text").val("[empire="+data.id+"]");
		}, "json");
	},
	getEmbedRapport:function(e){
		var id = e.attr("rapport_id");
		if (e.next(".rapport").length > 0)
		{
			e.next(".rapport").slideToggle();
		}
		else
		{
			$.get('/board/getRapport', {id:id}, function(data){
				if (!addError(data))
				{
					$(e).after(data.rapport);
					general();
				}
			}, "json");
		}
	},
	getEmbedEmpire:function(e){
		var id = e.attr("rapport_id");
		if (e.next(".rapport").length > 0)
		{
			e.next(".rapport").slideToggle();
		}
		else
		{
			$.get('/board/getReportEmpire', {id:id}, function(data){
				if (!addError(data))
				{
					$(e).after(data.rapport);
					general();
				}
			}, "json");
		}
	},
	getRapport:function(e){
		var id = e.attr("rapport_id");
		$.get('/board/getRapport', {id:id}, function(data){
			if (!addError(data))
			{
				e.parent().removeClass("label-important");
				$("#rapport_content").html(data.rapport);
				general();
			}
		}, "json");
	},
	loadMission:function(e){
		var id = e.attr("mission_id");
		$("#missions_list").find('li').removeClass("active");
		e.addClass("active");
		$(".mission").hide();
		$("#mission_"+id).show();
	},
	addShip:function(e){
		var dom = e.parent().find("input");
		dom.val(parseInt(dom.val()) + 1);
		$f.exec("board:sortShip");
	},
	sendFlotte:function(e){
		$("#move_flotte").submit();
	},
	minusShip:function(e)
	{
		var dom = e.parent().find("input");
		var new_nb = parseInt(dom.val()) - 1;
		new_nb = (new_nb < 0) ? 0 : new_nb;
		dom.val(new_nb);
		$f.exec("board:sortShip");
	},
	sortShip:function(e){
		var conso = 0;
		var stockage = 0;

		$(".ship_nb input").each(function(){
			conso += $(this).val() * parseInt($(this).attr("tetranium"));
			stockage += $(this).val() * parseInt($(this).attr("stockage"));
		});
		if ($("#conso_tetranium") != undefined)
			$("#conso_tetranium").html(conso);
		if ($("#conso_stockage") != undefined)
			$("#conso_stockage").html(stockage);
	},
	setOnglet:function(e){
		var onglet = $(e).attr("href");
		setCookie("empire_onglet", onglet, 1);
	},
	visualize:function(e){
		if (e.attr("planet_id"))
		{
			$("#preview_planete").removeClass("off");
			$.get("/board/previewPlanet", {id:e.attr("planet_id")}, function(data){
				if (!addError(data))
					$("#preview_planete").html(data._html_);
			}, "json");
		}
	},
	changeGalaxie:function(e){
		var options = {galaxie:$("select[name=galaxie]").val()};
		if ($("input[name=orbites]").is(":checked"))
			options.orbites = true;
		if ($("input[name=coords]").is(":checked"))
			options.coords = true;
		if ($("input[name=planet_unhabited]").is(":checked"))
			options.planet_unhabited = true;
		if ($("input[name=planet_players]").is(":checked"))
			options.planet_players = true;
		visualize_galaxie(options);
	}
};
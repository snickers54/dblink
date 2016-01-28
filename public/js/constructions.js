$(document).ready(function(){
	activeOnglet();
	addCompteurs();
});

function addCompteurs()
{
	$.get('/board/currentConstruct', {}, function(data){
		for (var i in data)
		{
			var compteur = getCompteur(data[i].time, data[i].totaltime, i);
			$("#"+i).removeClass("off");
			$("#"+i).find('.current .compteur').html(compteur);
			if (data[i].level != undefined)
				$("#"+i).find('.current .compteur').prepend("<b>"+data[i].nom+" ["+data[i].level+"]</b>");
			else
				$("#"+i).find('.current .compteur').prepend("<b>"+data[i].number+" "+data[i].nom+"</b>");
			$("#"+i).find('.current .compteur').prepend('<i class="icon-remove icon-white cursor pull-right" code="'+i+'" click="constructions:cancel"></i>&nbsp;');
			$("#"+i).find('.current').removeClass("off");	
		}
	}, "json");
}

function getCompteur(time, totaltime, prefix)
{
	var percent = Math.round((totaltime - time) * 100 / totaltime);
	var ts = Math.round((new Date()).getTime() / 1000);
	if (prefix != undefined)
		ts = prefix + ts;
	var compter = "<div class='progress progress-success progress-striped active'>";
	compter += "<div class='bar bar_compteur_ajax_"+ts+"' style='width:"+percent+"%'></div>";
	compter += "<span class='timebar'><compter id='compteur_ajax_"+ts+"'></compter></span>";
	compter = $(compter);
	compter.find("#compteur_ajax_"+ts).compter({time: time, totaltime:totaltime ,showUnits: true});
	return compter;
};

function activeOnglet()
{
	var onglet = getCookie("constructions_onglet");
	if (onglet)
	{
		$("#batiments").removeClass("active");
		$("#batiments_li").removeClass("active");
		$(onglet).addClass("active");
		$(onglet + "_li").addClass("active");
	}	
};

$f.constructions = {
	setOnglet:function(e){
		var onglet = $(e).attr("href");
		setCookie("constructions_onglet", onglet, 1);
	},
	levelUp:function(e){
		var code = $(e).attr("building_code");
		$.post('/board/construct', {code:code}, function(data){
			addError(data);
			if (addSuccess(data))
				addCompteurs();
		}, "json");
	},
	numberUp:function(e){
		var code = $(e).attr("building_code");
		var number = parseInt($(e).parent().find('input[type=text]').val());
		if (number != NaN && number > 0)
			$.post('/board/construct', {code:code, number:number}, function(data){
				addError(data);
				if (addSuccess(data))
				{
					addCompteurs();
					$(e).parent().find('input[type=text]').val("");
				}
			}, "json");
	},
	cancel:function(e)
	{
		var code = $(e).attr("code");
		$.post('/board/cancel', {code:code}, function(data){
			if (addSuccess(data))
			{
				$("#"+code).find('.compter').html('');
				$("#"+code).find('.current').addClass('off');
			}
			addError(data);
		}, "json");
	}
};
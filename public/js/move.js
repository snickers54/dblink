$(document).ready(function(){
	addCompteurs();
});

function addCompteurs()
{
	$("#move_flotte").find(".current").each(function(){
			var time = $(this).attr("time");
			var totaltime = $(this).attr("totaltime");
			var i = $(this).attr("move_id");
			var compteur = getCompteur(time, totaltime, i);
			
			$(this).removeClass("off");
			$(this).find('.compteur').html(compteur);
		});
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
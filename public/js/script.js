$(document).ready(function(){
	$f.exec("script:calc");
});

function calc_growth(start, pourcent, level)
{
	for (var i = 1; i < level; i++)
		start *= pourcent;
	return start;
}

function calc_mine(prefix, start, pourcent)
{
	$("#"+prefix+"_levels").html("");
	for (var level = 1; level < 45; level++)
	{
		var growth = Math.round(calc_growth(start, pourcent, level) * 100) / 100;
		$("#"+prefix+"_levels").append("Level : "+level+" | "+prefix+"/h : "+growth+"<br />");
	}
}

$f.script = {
	calc:function(e)
	{
		calc_mine("metal", parseInt($("input[name=start_grow_metal]").val()), 1 + (parseInt($("input[name=percent_grow_metal]").val()) / 100));
		calc_mine("tetranium", parseInt($("input[name=start_grow_tetranium]").val()), 1 + (parseInt($("input[name=percent_grow_tetranium]").val()) / 100));
		calc_mine("cristal", parseInt($("input[name=start_grow_cristal]").val()), 1 + (parseInt($("input[name=percent_grow_cristal]").val()) / 100));
	},
	showB:function(e)
	{
		var code = e.val();
		$.get('/script/showB', {code:code}, function(data){
			$("#showB").html(data._html_);
		}, "json");
	},
	calculB:function(e)
	{
		console.log(e);
		$("#content_showB").html("");
		var level_max = parseInt(e.parent().parent().find("input[name=level_max]").val());
		var factor_time = parseInt(e.parent().parent().find("input[name=factor_time]").val());
		var metaux_base = parseInt(e.parent().parent().find("input[name=metaux_base]").val());
		var cristaux_base = parseInt(e.parent().parent().find("input[name=cristaux_base]").val());
		var tetranium_base = parseInt(e.parent().parent().find("input[name=tetranium_base]").val());
		var population_base = parseInt(e.parent().parent().find("input[name=population_base]").val());
		var energie_base = parseInt(e.parent().parent().find("input[name=energie_base]").val());
		var cost_augmentation = parseInt(e.parent().parent().find("input[name=cost_augmentation]").val());
		$.get('/script/calculB', {level_max: level_max, factor_time:factor_time, metaux_base:metaux_base, cristaux_base: cristaux_base, population_base:population_base, tetranium_base:tetranium_base, energie_base:energie_base, cost_augmentation:cost_augmentation}, function(data){
			$("#content_showB").html(data._html_);
		}, "json");
	}

};
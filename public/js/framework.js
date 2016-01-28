/*
 * New framewrok
 */


// Fonction qui remplace alert(); plus jolie ...
function $f_alert(value)
{
	function getObject(obj, recursion){
		if (typeof(obj) == "object"){
			var value = "{<br/>";
			var i = 0;
			for (variable in obj){
				if (typeof(variable) == "function")
					return "function()";
				$f.log
				if (i++ != 0)
					value += ',<br/>';
				for (var i = 0; i <= recursion; i++)
					value += "&nbsp&nbsp&nbsp";
				value +=  variable + " : ";
				value += getObject(obj[variable], recursion+1)
			}
			value += "<br/>"
			for (var i = 0; i < recursion; i++)
				value += "&nbsp&nbsp&nbsp";
			value += "}";
			return value
		}
		else
			return obj;
	}

	value = getObject(value, 0);

	dialog({
		content : value,
		callback : function(){}
	});
};

function $f_loadmodule(module, callback) {
	var url = $f.config.path+"/"+module+".js";
	$.ajax({
		type: "GET",
		url: url,
		dataType: "script",
		success: function(data, textStatus) {
			if (callback){
				callback();
			}
		},
		error: function(xhr, ajaxOptions, thrownError){
			if ($f.config.debug == true)
				console.error("loadComponent : type["+ajaxOptions+"], erreur["+thrownError+"], impossible d'atteindre ["+url+"]");
		}
	});
}

// Fonction qui permet d'executer une fonction autre que $f.myModule.myAction()
function $f_exec(value, el){
	var module = value.split($f.config.separator, 2)[0];
	var action = value.split($f.config.separator, 2)[1];
	var success =
		$f_dispatch({
			'module': module,
			'action': action,
			'target': el
		});
	if (!success){
		$f_loadmodule(module, function(){
			$f_dispatch({
				'module': module,
				'action': action,
				'target': el,
				'error': true
			});
		});
	}
}

// Fonction qui renvoie un obj contenant les elements d'un formulaire
function $f_getform(id)
{
	var elem = $("form#"+id).first();
	var obj = new Object();
	var count = 0;
	elem.find("input, textarea, select").each(function(index){
		var name = $(this).attr("name");
		if (name == undefined){
			name = "no_name_"+count++;
			if ($f.config.debug == true)
				console.warn("Attribut `name` non definie. Valeur attribue {"+name+"}");
		}
		var value = $(this).val();
		obj[name] = value;
	});
	var info = {
		"_url_": elem.attr("action"),	
		"_type_": (elem.attr("type") != undefined)?(elem.attr("type")):("POST")
	}
	jQuery.extend(obj, info);
	return obj;
}

function $f_sendform(id, csuccess, cerror)
{
	var obj = $f.getform(id);
	$.ajax({
		type: obj._type_,
		url: obj._url_,
		dataType: "JSON",
		data: obj,
		success: csuccess,
		error: cerror
	});
}

function $f_dispatch(options){
	var mod,act,funct;
	eval("mod = $f."+options.module);
	if (mod != undefined)
	{
		eval("act = mod."+options.action);
		if (act != undefined)
		{
			eval("funct = $f."+options.module+"."+options.action);
			funct(options.target);
		}
		else if (options.error && $f.config.debug == true)
			console.warn("La fonction ["+options.action+"] n'est pas definie dans le module ["+options.module+"].");
		else
			return false;
	}
	else if (options.error && $f.config.debug == true)
		console.warn("Le module ["+options.module+"] n'existe pas.");
	else
		return false;
	return true;
}

function $f_log(obj)
{
	if (window.navigator.appName != 'Microsoft Internet Explorer')
		if ($f.config.debug == true)
			console.log(obj);
}
function $f_info(obj)
{
	if (window.navigator.appName != 'Microsoft Internet Explorer')
		if ($f.config.debug == true)
			console.info(obj);
}
function $f_warn(obj)
{
	if (window.navigator.appName != 'Microsoft Internet Explorer')
		if ($f.config.debug == true)
			console.warn(obj);
}
function $f_error(obj)
{
	if (window.navigator.appName != 'Microsoft Internet Explorer')
		if ($f.config.debug == true)
			console.error(obj);
}
window.$f  = {

	config:{
		separator			: 	':',
		path				: 	"/public/js",
		debug				: 	true
	},

	alert		: 	$f_alert,
	exec		: 	$f_exec,
	getform		: 	$f_getform,
	sendform	: 	$f_sendform,

	// Fonctions de logs
	log			: 	$f_log, 
	info		: 	$f_info, 
	warn		: 	$f_warn, 
	error		: 	$f_error, 
}

function dispatcher(e){$f.exec($(this).attr(e.type), $(this))};
	
$("[click]").live("click", dispatcher);
$("[dbclick]").live("dbclick", dispatcher);
$("[mouseenter]").live("mouseenter", dispatcher);
$("[mouseleave]").live("mouseleave", dispatcher);
$("[focusin]").live("focusin", dispatcher);
$("[keyup]").live("keyup", dispatcher);
$("[focusout]").live("focusout", dispatcher);
$("[change]").live("change", dispatcher);

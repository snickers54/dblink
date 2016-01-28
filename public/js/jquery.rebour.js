(function($){
    $.fn.compter = function(options) {
	var defaults = {
	    time: 0,
	    totaltime: 0,
	    separator: ':',
	    msgEnd: 'Fini',
	    nbZero: '0',
	    nbOne: '1',
	    nbTwo: '2',
	    nbThree: '3',
	    nbFour: '4',
	    nbFive: '5',
	    nbSix: '6',
	    nbSeven: '7',
	    nbHeight: '8',
	    nbNine: '9',
	    showUnits: false,
	    unitSeconds: "s ",
	    unitMinutes: "m ",
	    unitHours: "h ",
	    unitDays: "j ",
	    unitMax: 'h',
	    jsEachTime: '',
	    jsEndTime: ''
	    //jsEndTime: 'document.location.href=window.location.pathname',
	};
	var opts = $.extend(defaults, options);

	function	getNb(nb)
	{
	    switch (nb) {
	    case 0: return opts.nbZero;
	    case 1: return opts.nbOne;
	    case 2: return opts.nbTwo;
	    case 3: return opts.nbThree;
	    case 4: return opts.nbFour;
	    case 5: return opts.nbFive;
	    case 6: return opts.nbSix;
	    case 7: return opts.nbSeven;
	    case 8: return opts.nbHeight;
	    case 9: return opts.nbNine;
	    default: return '';
	    }
	}

	function	nbFormat(value){
	    res = "";
	    size = ("" + value).length ;
	    for (puiss = 1; size > 1; puiss *= 10, size--);
	    for (; puiss >= 1; value %= puiss, puiss /= 10)
		res += getNb(Math.floor(value / puiss));
	    return res;
	}
		
	function	Normalize(){
	    var label = new Array(days, hours, minutes, seconds);
	    for (i in label){
		value = label[i];
		label[i] = "";
		if (value < 10)
		    label[i] += getNb(0) + getNb(value);
		else
		    label[i] += nbFormat(value);
	    }
	    days = label[0]; hours = label[1]; minutes = label[2]; seconds = label[3];
	    if (opts.showUnits == true){
		(opts.unitMax == 'd') ? (days += opts.unitDays) : (days = '');
		(opts.unitMax == 'd' || opts.unitMax == 'h') ? (hours += opts.unitHours) : (hours = '');
		(opts.unitMax == 'd' || opts.unitMax == 'h' || opts.unitMax == 'm') ? (minutes += opts.unitMinutes) : (minutes = '');
		(opts.unitMax == 'd' || opts.unitMax == 'h' || opts.unitMax == 'm' || opts.unitMax == 's') ? (seconds += opts.unitSeconds) : (seconds = '');
	    }
	    else{
		days += opts.separator;
		hours += opts.separator;
		minutes += opts.separator;
	    }
	}
	function Calcul(){
	    var save = Compt;
		    
	    for (days = 0; Compt >= 86400 && opts.unitMax == 'd'; days++)
		Compt -= 86400;
	    for (hours = 0; Compt >= 3600 && (opts.unitMax == 'd' || opts.unitMax == 'h'); hours++)
		Compt -= 3600;
	    for (minutes = 0; Compt >= 60 && (opts.unitMax == 'd' || opts.unitMax == 'h' || opts.unitMax == 'm'); minutes++)
		Compt -= 60;
	    for (seconds = 0; Compt > 0 && (opts.unitMax == 'd' || opts.unitMax == 'h' || opts.unitMax == 'm' || opts.unitMax == 's'); seconds++)
		Compt--;
	    Compt = save;
	    Normalize();
	}
	function CalculTime(){
	    Start = new Date();
	    Time = Start.getTime();
	    TimeStart = Start.getTime();
	    TimeEnd = TimeStart + opts.time--;// + Le temp passe en arg
	    if ((Compt = Math.floor(TimeEnd - TimeStart)) <= 0)
		Compt = 0;
	    if (opts.time + 1 < 0)
		return false;
	    Calcul();
	    return true;
	}
	function Rebours(id){
	    if (CalculTime()){		
		var percent = Math.round((opts.totaltime - opts.time) * 100 / opts.totaltime);
		var Show = days + hours + minutes + seconds;
		$("#"+id).html(Show+'<script type="text/javascript">$(".bar_'+id+'").css("width", "'+percent+'%");</script>');
	    }
	    else{
			$("#"+id).html(opts.msgEnd+'<script type="text/javascript">'+opts.jsEndTime+'</script>');
			clearInterval(Ident);
	    }
	}
	Function.prototype.callBack=function(){
	    var __method = this, args = Array.prototype.slice.call(arguments,0);
	    return function() {
		return __method.apply(this, args);
	    }
	}
	$(this).each(function(){
		var id = this.id;
		Ident = setInterval(Rebours.callBack(id), 1000);
	    });
		
	return $(this);
    };
})(jQuery);
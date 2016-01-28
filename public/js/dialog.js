// Apprise 1.5 by Daniel Raftery
// http://thrivingkings.com/apprise
//
// Button text added by Adam Bezulski
//

function dialog(args){
	
	var defaults =
	{
		content 		: 	"",
		confirm			: 	false, 		// Ok and Cancel buttons
		verify			:	false,		// Yes and No buttons
		input			:	false, 		// Text input (can be true or string for default text)
		animate			:	false,		// Groovy animation (can true or number, default is 400)
		textOk			:	'Ok',		// Ok button default text
		textCancel		: 	'Cancel',	// Cancel button default text
		textYes			: 	'Yes',		// Yes button default text
		textNo			:	'No',		// No button default text
		callback		:	function(r){}
	}
		
	var options = $.extend(defaults, args);
	var aHeight = $(document).height();
	var aWidth = $(document).width();
	
	$('body').append('<div class="appriseOverlay" id="aOverlay"></div>');
	$('.appriseOverlay').fadeIn(100);
	$('body').append('<div class="appriseOuter"></div>');
	$('.appriseOuter').append('<div class="appriseInner"></div>');
	$('.appriseInner').append(options['content']);
    $('.appriseOuter').css("left", ( $(window).width() - $('.appriseOuter').width() ) / 2+$(window).scrollLeft() + "px");
    
    if(options){
    	if(options['animate']){ 
			var aniSpeed = options['animate'];
			if(isNaN(aniSpeed)) { aniSpeed = 400; }
			$('.appriseOuter').css('top', '-200px').show().animate({top:"100px"}, aniSpeed);
		}
		else{
			$('.appriseOuter').css('top', '100px').fadeIn(200);
		}
	}
	else{
		$('.appriseOuter').css('top', '100px').fadeIn(200);
	}
    
    if(options){
    	if(options['input']){
    		if(typeof(options['input'])=='string'){
    			$('.appriseInner').append('<div class="aInput"><input type="text" class="aTextbox" t="aTextbox" value="'+options['input']+'" /></div>');
    		}
    		else{
    			$('.appriseInner').append('<div class="aInput"><input type="text" class="aTextbox" t="aTextbox" /></div>');
			}
			$('.aTextbox').focus();
    	}
    }
    
    $('.appriseInner').append('<div class="aButtons"></div>');
    if(options){
		if(options['confirm'] || options['input']){ 
			$('.aButtons').append('<button value="ok">'+options['textOk']+'</button>');
			$('.aButtons').append('<button value="cancel">'+options['textCancel']+'</button>'); 
		}
		else if(options['verify']){
			$('.aButtons').append('<button value="ok">'+options['textYes']+'</button>');
			$('.aButtons').append('<button value="cancel">'+options['textNo']+'</button>');
		}
		else{
			$('.aButtons').append('<button value="ok">'+options['textOk']+'</button>');
		}
	}
    else{
    	$('.aButtons').append('<button value="ok">Ok</button>');
    }

	$(document).keydown(function(e) {
		var escape = 27;
		var enter = 13;
		if($('.appriseOverlay').is(':visible')){
			if(e.keyCode == enter){
				$('.aButtons > button[value="ok"]').click();
			}
			if(e.keyCode == escape){
				$('.aButtons > button[value="cancel"]').click();
			}
		}
	});
	
	var aText = $('.aTextbox').val();
	if(!aText) { aText = false; }
	$('.aTextbox').keyup(function()
    	{ aText = $(this).val(); });
   
    $('.aButtons > button').click(function()
    	{
    	$('.appriseOverlay').remove();
		$('.appriseOuter').remove();
    	if(options['callback'])
    		{
			var wButton = $(this).attr("value");
			if(wButton=='ok')
				{ 
				if(options)
					{
					if(options['input'])
						{ options['callback'](aText); }
					else
						{ options['callback'](true); }
					}
				else
					{ options['callback'](true); }
				}
			else if(wButton=='cancel')
				{ options['callback'](false); }
			}
		});
}
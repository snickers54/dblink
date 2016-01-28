Completion_User = new Array();
mailwarn = 0;

blinkTimer = false;
$(document).ready(function(){
    timeoutNotifications($('.notifications'), 0);
    refreshRessources();
    general();
    $("body").mouseover(function(){
        if (blinkTimer){
           window.clearTimeout(blinkTimer);
           $(document).attr("title", "DBLINK");
        }
        blinkTimer = false;
    });
    event_compter();
});

function blink_chat()
{
    $(document).attr("title", (!document.title || document.title == "DBLINK") ? "DBLINK - Message Waiting !" : "DBLINK");
    blinkTimer = window.setTimeout(blink_chat, 1000);
}

function resizeAllTextarea()
{
    $('textarea').live('keyup', function(){
        resizeTextarea(this);
    });
}

function resizeTextarea(t) {
    lines = t.value.split('\n');
    lineLen = 0;
    var maxLen = 3;
    for (x = 0; x < lines.length; ++x)
    {
        if (lines[x].length > maxLen)
            maxLen = lines[x].length;
    }
    for (x = 0; x < lines.length; ++x) {
        if (lines[x].length >= t.cols) {
            lineLen += Math.floor(lines[x].length / t.cols);
        }
    }
    lineLen += lines.length + 1;
    t.rows = lineLen;

}

function event_compter()
{
    if ($(".current_event").length > 0 )
    {
        $(".current_event").each(function(){
            var event_id = parseInt($(this).attr("event_id"));
            var time_start = parseInt($(this).attr("time_start"));
            var time_end = parseInt($(this).attr("time_end"));
            var totaltime = time_end - time_start;
            var time = totaltime - ((Math.round((new Date()).getTime() / 1000)) - time_start); 
            $(this).find("#compteur_ajax_event"+event_id).compter({time: time, totaltime:totaltime ,showUnits: true});
        });
    }
}


function general()
{
    $(".carousel.freeze").carousel('pause');
    $('.tip').tooltip();
    $("#mytTip").remove();
    $(".tTip").wTooltip({
     id:"mytTip",
     style: false
   });
    if (!mailwarn)
        mailWarn();
    $(".pop").popover({html:true, trigger:'hover'});
    timeoutNotifications($('.tooltip'), 2);
    timeoutNotifications($('.popover:not(.freeze)'), 2);
    resizeAllTextarea();
    evolutiveDate(10000);
}

function mailWarn()
{
    if ($(".mail-warn"))
    {
        mailwarn = 1;
        var img = $(".mail-warn");
        img.each(function(){
            var imgs = $(this).find("i");
            if (imgs.first().hasClass("off"))
            {
                imgs.first().removeClass("off");
                imgs.last().addClass("off");
            }
            else
            {
                imgs.last().removeClass("off");
                imgs.first().addClass("off");
            }
        });
        setTimeout(function(){mailWarn();}, 800);
    }
}

function evolutiveDate()
{
    var current = new Date();
    var current_string = current.getDate() + "/" + (current.getMonth() + 1) + "/" + current.getFullYear();

    $(".date").each(function(){
        var timestamp = parseInt($(this).attr("time")) * 1000;
        var date = new Date(timestamp);
        var date_string = date.getDate() + "/" + (date.getMonth() + 1) + "/" + date.getFullYear();
        var hours_string = ((date.getHours() < 10) ? ("0" + date.getHours()) : (date.getHours()))
                            + ":"
                            + ((date.getMinutes() < 10) ? ("0" + date.getMinutes()) : (date.getMinutes()));
        var diff = Math.round(current.getTime() / 1000) - Math.round(date.getTime() / 1000);
        if (diff < 3600)
        {
            var m, s;
            s = diff % 60;
            m = diff - s;
            m /= 60;
            $(this).html(((m > 0) ? (m + " minutes") : (s + " secondes")));
        }
        else if (current_string == date_string)
            $(this).html(hours_string);
        else
            $(this).html(hours_string + " - " + date_string);
    });
}

function refreshBlockLeft()
{
    $.get('/user/refreshLeft', {}, function(data){
        $("#block_left").html(data._html_);
    }, "json");
}

function refreshRessources()
{
    var time = getCookie("ressources_time");
    if (time == null || time == undefined || time < 5000)
        time = 10000;
    $.get('/user/refreshRessources', {}, function(data){
        $("#block_planet_left").html(data._html_);
        general();
        if (data._error_ == undefined)
            setTimeout(function(){refreshRessources(time);}, time);
    }, "json");
}

function timeoutNotifications(dom, i)
{
    if (dom && dom.length > 0)
    {
        if (i == 3)
        {
            dom.fadeOut('slow', function(){
                $(this).remove();
            });
        }
        setTimeout(function() { timeoutNotifications(dom, ++i); }, 1000);
    }
}

function goTop()
{
        $('html,body').animate({scrollTop: 0}, 'slow');
}

function goBot()
{
    $('html,body').animate({scrollTop: $('html, body')[0].scrollHeight}, 'slow');
}

function addError(object)
{
        if (object._error_)
        {
                $(".notifications").remove();
                var error = '<div class="row-fluid" class="notifications">' +
                                '<div class="span12">' +
                                        '<div class="alert alert-error">'+
                                            '<div class="row-fluid">'+
                                                '<div class="span1">'+
                                                    '<img src="/public/images/avatar/robotdblink.jpg" class="avatar-medium label label-inverse"/>'+
                                                '</div>'+
                                                '<div class="span11"><b>'+
                                                    object._error_ +
                                                '</b></div>'+
                                            '</div>'+
                                        '</div>'+
                                '</div>'+
                            '</div>';
                error = $(error);
                $("#main-content").prepend(error);
                goTop();
                timeoutNotifications(error, 0);
                return true;
        }
        return false;
}

function addAchievement(object)
{
    if (object._achievement_)
    {
            $(".notifications").remove();
            var achievement = '<div class="row-fluid" class="notifications">' +
                            '<div class="span12">' +
                                    '<div class="alert alert-info">'+
                                        '<div class="row-fluid">'+
                                            '<div class="span1">'+
                                                '<img src="'+object._achievement_.avatar+'" class="avatar-medium label label-inverse"/>'+
                                            '</div>'+
                                            '<div class="span11"><b>'+
                                                object._achievement_.msg +
                                            '</b></div>'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'+
                        '</div>';
            achievement = $(achievement);
            $("#main-content").prepend(achievement);
            goTop();
            timeoutNotifications(achievement, 0);
            return true;
    }
    return false;    
}

function addSuccess(object)
{
        if (object._success_)
        {
                $(".notifications").remove();                
                var success = '<div class="row-fluid" class="notifications">' +
                                '<div class="span12">' +
                                        '<div class="alert alert-success">'+
                                            '<div class="row-fluid">'+
                                                '<div class="span1">'+
                                                    '<img src="/public/images/avatar/robotdblink.jpg" class="avatar-medium label label-inverse"/>'+
                                                '</div>'+
                                                '<div class="span11"><b>'+
                                                    object._success_ +
                                                '</b></div>'+
                                            '</div>'+
                                        '</div>'+
                                '</div>'+
                            '</div>';
                success = $(success);
                $("#main-content").prepend(success);
                goTop();
                timeoutNotifications(success, 0);
                return true;
        }
        return false;
}

// a n'utiliser que lorsqu'on change completement de planete ou autre mais pas tout le temps car c'est lourd
function reloadContent(url)
{
    if (url == undefined)
        var url = window.location.pathname + "Action";
    $("#loader").show();
    $.get(url, {}, function(data){
        if (addError(data))
            return ;
        addSuccess(data);
        $("#view_content").html(data._html_);
    }, "json").complete(function(){
        $("#loader").hide();
    });
};


function setCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
};

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
};

function deleteCookie(name) {
    setCookie(name,"",-1);
};

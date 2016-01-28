$(function () {
    $(document).ready(function() {
        Highcharts.theme={colors:["#DDDF0D","#55BF3B","#DF5353","#7798BF","#aaeeee","#ff0066","#eeaaee","#55BF3B","#DF5353","#7798BF","#aaeeee"],chart:{backgroundColor:{linearGradient:[0,0,250,500],stops:[[0,"rgb(48, 48, 96)"],[1,"rgb(0, 0, 0)"]]},borderColor:"#000000",borderWidth:2,className:"dark-container",plotBackgroundColor:"rgba(255, 255, 255, .1)",plotBorderColor:"#CCCCCC",plotBorderWidth:1},title:{style:{color:"#C0C0C0",font:'bold 16px "Trebuchet MS", Verdana, sans-serif'}},subtitle:{style:{color:"#666666",font:'bold 12px "Trebuchet MS", Verdana, sans-serif'}},xAxis:{gridLineColor:"#333333",gridLineWidth:1,labels:{style:{color:"#A0A0A0"}},lineColor:"#A0A0A0",tickColor:"#A0A0A0",title:{style:{color:"#CCC",fontWeight:"bold",fontSize:"12px",fontFamily:"Trebuchet MS, Verdana, sans-serif"}}},yAxis:{gridLineColor:"#333333",labels:{style:{color:"#A0A0A0"}},lineColor:"#A0A0A0",minorTickInterval:null,tickColor:"#A0A0A0",tickWidth:1,title:{style:{color:"#CCC",fontWeight:"bold",fontSize:"12px",fontFamily:"Trebuchet MS, Verdana, sans-serif"}}},tooltip:{backgroundColor:"rgba(0, 0, 0, 0.75)",style:{color:"#F0F0F0"}},toolbar:{itemStyle:{color:"silver"}},plotOptions:{line:{dataLabels:{color:"#CCC"},marker:{lineColor:"#333"}},spline:{marker:{lineColor:"#333"}},scatter:{marker:{lineColor:"#333"}},candlestick:{lineColor:"white"}},legend:{itemStyle:{font:"9pt Trebuchet MS, Verdana, sans-serif",color:"#A0A0A0"},itemHoverStyle:{color:"#FFF"},itemHiddenStyle:{color:"#444"}},credits:{style:{color:"#666"}},labels:{style:{color:"#CCC"}},navigation:{buttonOptions:{backgroundColor:{linearGradient:[0,0,0,20],stops:[[.4,"#606060"],[.6,"#333333"]]},borderColor:"#000000",symbolStroke:"#C0C0C0",hoverSymbolStroke:"#FFFFFF"}},exporting:{buttons:{exportButton:{symbolFill:"#55BE3B"},printButton:{symbolFill:"#7797BE"}}},rangeSelector:{buttonTheme:{fill:{linearGradient:[0,0,0,20],stops:[[.4,"#888"],[.6,"#555"]]},stroke:"#000000",style:{color:"#CCC",fontWeight:"bold"},states:{hover:{fill:{linearGradient:[0,0,0,20],stops:[[.4,"#BBB"],[.6,"#888"]]},stroke:"#000000",style:{color:"white"}},select:{fill:{linearGradient:[0,0,0,20],stops:[[.1,"#000"],[.3,"#333"]]},stroke:"#000000",style:{color:"yellow"}}}},inputStyle:{backgroundColor:"#333",color:"silver"},labelStyle:{color:"silver"}},navigator:{handles:{backgroundColor:"#666",borderColor:"#AAA"},outlineColor:"#CCC",maskFill:"rgba(16, 16, 16, 0.5)",series:{color:"#7798BF",lineColor:"#A6C7ED"}},scrollbar:{barBackgroundColor:{linearGradient:[0,0,0,20],stops:[[.4,"#888"],[.6,"#555"]]},barBorderColor:"#CCC",buttonArrowColor:"#CCC",buttonBackgroundColor:{linearGradient:[0,0,0,20],stops:[[.4,"#888"],[.6,"#555"]]},buttonBorderColor:"#CCC",rifleColor:"#FFF",trackBackgroundColor:{linearGradient:[0,0,0,10],stops:[[0,"#000"],[1,"#333"]]},trackBorderColor:"#666"},legendBackgroundColor:"rgba(0, 0, 0, 0.5)",legendBackgroundColorSolid:"rgb(35, 35, 70)",dataLabelsColor:"#444",textColor:"#C0C0C0",maskColor:"rgba(255,255,255,0.3)"}
        Highcharts.setOptions({
            global: {
                useUTC: false
            }
        });
        var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
        $.get('/bourse/getLast', {}, function(data){
            charts_ressources("chart_metal", "Indice Métal : " + data.indices[data.indices.length - 1].metal+ " DBGolds", data);
            charts_ressources("chart_cristal", "Indice Cristal : " + data.indices[data.indices.length - 1].cristal+ " DBGolds", data);
            charts_ressources("chart_tetranium", "Indice Tétranium : " + data.indices[data.indices.length - 1].tetranium+ " DBGolds", data);
            charts_ressources("chart_energie", "Indice Energie : " + data.indices[data.indices.length - 1].energie+ " DBGolds", data);
        }, "json");

function charts_ressources(id, title, mydata)
{

// Apply the theme
   var chart;
        chart = new Highcharts.Chart({
            chart: {
                renderTo: id,
                type: 'spline',
                marginRight: 10,
                events: {
                    load: function() {
    
                        // set up the updating of the chart each second
                        var series = this.series[0];
                        var p = this;
                        setInterval(function() {
                            $.get('/board/getLastBourse', {last:true, code:id}, function(mydata){
                                var value = 0;
                                mydata = mydata.indices;                                
                                for (i in mydata) {
                                    if (id == "chart_metal")
                                    {
                                        indice = "Indice Métal";
                                        value = mydata[i].metal;
                                    }
                                    if (id == "chart_cristal")
                                    {
                                        indice = "Indice Cristal";
                                        value = mydata[i].cristal;
                                    }
                                    if (id == "chart_tetranium")
                                    {
                                        indice = "Indice Tétranium";
                                        value = mydata[i].tetranium;
                                    }
                                    if (id == "chart_energie")
                                    {
                                        indice = "Indice Energie";
                                        value = mydata[i].energie;
                                    }
                                    var x = (parseInt(mydata[i].timestamp) * 1000);
                                    var y = value;
                                    series.addPoint([x, y], true, true);
                                    chart.setTitle({text: indice + " : " + value + " DBGolds"});
                                }
                                calc_price(id);
                            }, "json");
                        }, 60000);
                    }
                }
            },
            title: {
                text: title
            },
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 150
            },
            yAxis: {
                title: {
                    text: 'DBGolds'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            plotOptions: {
            	line: {
            		dataLabels: {
            			enabled:true
            		},
            		enableMouseTracking:false
            	}
            },
            tooltip: {
                formatter: function() {
                        return '<b>'+Highcharts.numberFormat(this.y, 2)+'</b>';
                }
            },
            legend: {
                enabled: false
            },
            exporting: {
                enabled: false
            },
            series: [{
                name: '',
                data: (function() {
                    // generate an array of random data
                    var data = [];
                    mydata = mydata.indices;
                    var value = 0;
                    for (i in mydata) {
                        if (id == "chart_metal")
                            value = mydata[i].metal;
                        if (id == "chart_cristal")
                            value = mydata[i].cristal;
                        if (id == "chart_tetranium")
                            value = mydata[i].tetranium;
                        if (id == "chart_energie")
                            value = mydata[i].energie;
                        data.push({
                            x: (parseInt(mydata[i].timestamp) * 1000),
                            y: value
                        });
                    }
                    return data;
                })()
            }]
        });
}
        function calc_price(id)
        {
            var type = $("#bourse_buy_select").val();
            if (id == undefined || id == "chart_"+type)
            {
                var number = parseInt($("#bourse_buy_input").val());
                if (number)
                    $.get('/bourse/calcPrice', {number:number, type:type}, function(data){
                        $(".total_actions_cost").html("<b>"+number + " action(s) #"+type+" : </b><span class='badge badge-success'>"+data.total+ " dbgolds</span>");
                    }, "json");
                else
                    $(".total_actions_cost").html("");
            }
        };

        $f.bourse = {
            calc_price: function(e)
            {
                calc_price();
            },
            buy_actions: function(e)
            {
                var type = $("#bourse_buy_select").val();
                var number = parseInt($("#bourse_buy_input").val());
               if (number)
                    $.post('/bourse/buy', {number:number, type:type}, function(data){
                        addError(data);
                        if (addSuccess(data))
                        {
                            $("#myactions").html(data._html_);
                            $("#bourse_buy_input").val("");
                            $(".total_actions_cost").html("");
                            refreshBlockLeft();
                        }
                    }, "json");
            },
            sell:function(e)
            {
                var nb_metal = parseInt($("#bourse_sell").find("input[name=nb_metal]").val());
                var nb_cristal = parseInt($("#bourse_sell").find("input[name=nb_cristal]").val());
                var nb_tetranium = parseInt($("#bourse_sell").find("input[name=nb_tetranium]").val());
                var nb_energie = parseInt($("#bourse_sell").find("input[name=nb_energie]").val());
                $.post('/bourse/sell', {nb_metal:nb_metal, nb_cristal:nb_cristal, nb_tetranium:nb_tetranium, nb_energie:nb_energie}, function(data){
                    addError(data);
                    if (addSuccess(data))
                    {
                        $("#bourse_sell").find("input[name=nb_metal]").val(0);
                        $("#bourse_sell").find("input[name=nb_cristal]").val(0);
                        $("#bourse_sell").find("input[name=nb_energie]").val(0);
                        $("#bourse_sell").find("input[name=nb_tetranium]").val(0);
                        $("#myactions").html(data._html_);
                        refreshBlockLeft();
                    }
                }, "json");
            },
            exchange:function(e)
            {
                var nb_metal = parseInt($("#bourse_exchange").find("input[name=nb_metal]").val());
                var nb_cristal = parseInt($("#bourse_exchange").find("input[name=nb_cristal]").val());
                var nb_tetranium = parseInt($("#bourse_exchange").find("input[name=nb_tetranium]").val());
                $.post('/bourse/exchange', {nb_metal:nb_metal, nb_cristal:nb_cristal, nb_tetranium:nb_tetranium}, function(data){
                    addError(data);
                    if (addSuccess(data))
                    {
                        $("#bourse_exchange").find("input[name=nb_metal]").val(0);
                        $("#bourse_exchange").find("input[name=nb_cristal]").val(0);
                        $("#bourse_exchange").find("input[name=nb_tetranium]").val(0);
                        $("#myactions").html(data._html_);
                        refreshBlockLeft();
                    }
                }, "json");
            }
        };
    });
});
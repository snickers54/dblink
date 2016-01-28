var force, svg, vis;
var width, height;

function visualize_galaxie(options){
  delete svg;
  delete force;
  $("#protovis").html("");
    $.get('/board/datas', {width:width, height:height, galaxie: options.galaxie}, function(data){
      if (!addError(data))
      {
        force = d3.layout.force()
            // a setter en fonction de la distance avec le soleil ...
            .linkDistance(function(d){return d.distance;})
            .size([width, height]);

        svg = d3.select("#protovis").append("svg")
            .attr("width", width)
            .attr("height", height);

          force
              .nodes(data.nodes)
              .links(data.links)
              .start();

          var link = svg.selectAll("line.link")
              .data(data.links)
            .enter().append("line")
              .attr("class", "link")
              .style("stroke-width", 2);
        
        function orbite(d){

          if (options.orbites == undefined)
          {
            svg.selectAll(".orbite").remove();

            svg.insert("g", ".node")
            .attr("class", "orbite")
            .attr("transform", function(e){
                  return "translate(" + (width * 50 / 100) + "," + (height * 50 / 100) +")";
               })
            .append("svg:circle")
            .style("stroke", "#09C")
            .style("z-index", 2)
            .style("fill", "transparent")
            .style("stroke-opacity", 1)
            .transition()
            .duration(800)
            .attr("r", d.distance_soleil);
          }
          link.style("stroke", function(e){
              if (e.target.index == d.index)
                return "#fff";
              return "";
          });
        };

        function mouseover(e){
          svg.selectAll(".node")
          .style("opacity", function(d){
            return ((d.planet_id == e.planet_id)) ? 1 : 0.3;
          });          
        }
        function mouseout(d){          
          svg.selectAll(".node")
          .style("opacity", 1);
        };
          var node = svg.selectAll(".node")
              .data(data.nodes)
            .enter().append("g")
              .attr("class", "node")
              .attr("click", "board:visualize")
              .attr("planet_id", function(d){return d.planet_id;})
              .style("z-index", 20)
              .style("display", function(d){
                if (options.planet_unhabited == undefined && d.user_id && d.user_id < 1)
                  return "none";
                if (options.planet_players == undefined && d.user_id && d.user_id > 0)
                  return "none";
                return "block";
              })
              .on("mouseover", mouseover)
              .on("mouseout", mouseout)
              .on("click", orbite);
        

        function move(d) {
          function convertRadian(degree)
          {
            return degree * Math.PI / 180;
          };
          function new_coords(rayon, deviation) {
            var x, y, xs, ys;

            xs = width * 50 / 100;
            ys = height * 50 / 100;
            x = xs + rayon * Math.cos(convertRadian(deviation));
            y = ys + rayon * Math.sin(convertRadian(deviation));
            return {x:x, y:y};
          };
            var rayon = d.distance_soleil;
            var mytime = Math.round(+new Date() / 1000);
            var last_update = d.last_update;
            var nb_second = ((3600 * rayon) / 70) + 1;
            var deviation = ((((mytime - last_update) * 360) / nb_second) + parseFloat(d.teta)) % 360;
            d.last_update = mytime;
            d.teta = deviation;
            var coords = new_coords(rayon, d.teta);
            d.px = d.x;
            d.py = d.y;
            d.x = coords.x;
            d.y = coords.y;
            return coords;
        };

          if (options.orbites)
          {
            node.each(function(d){
              svg.insert("g", ".node")
              .attr("class", "orbite")
              .attr("transform", function(){
                    return "translate(" + (width * 50 / 100) + "," + (height * 50 / 100) +")";
                 })
              .append("svg:circle")
              .style("stroke", function(e){
                if ((options.planet_players && d.user_id > 0) ||
                  (options.planet_unhabited && d.user_id < 1))
                  return "#09C";
                return "transparent";
              })
              .style("z-index", 2)
              .style("fill", "transparent")
              .style("stroke-opacity", 1)
              .transition()
              .duration(800)
              .attr("r", d.distance_soleil);
            });
          }
        node.append("svg:text")
          .attr("dx", function(d){return - ((d.rayon / 4) + 10);})
          .attr("dy", function(d){return - (d.rayon+5);})
          .style("fill", "#FFF")
          .style("font-style", "italic")
          .text(function(d) { return d.label });
        
        if (options.coords)
        {
          node.append("svg:text")
            .attr("class", "coords")
            .attr("dx", function(d){return - ((d.rayon / 4) + 10);})
            .attr("dy", function(d){return ((d.rayon) + 15);})
            .style("fill", "#FFF")
            .style("font-style", "italic")
            .text(function(d) { return "(x = "+d.x + "; y = "+d.y+")"; });          
        }

        node.append("circle")
            .attr("r", function(d){return d.rayon;})
            .style("fill", function(d){
              if (!d.planet_id)
                return "#ffcc00";
              if (d.status == "moi")
                return "#167528";
              if (d.status == "alliance")
                return "#194575";
              if (d.status == "hostile")
                return "#B30C0C";
              if (d.status == "amis")
                return "#5B6F85";
              return "#FFF";
            }).style("stroke", "#000")
            .style("stroke-width", 2);
          
          node.attr("transform", function(d) {
            var coords = move(d);
            return "translate(" + coords.x + "," + coords.y + ")";
          });

          force.on("tick", function() {
             node.attr("transform", function(d){
                var coords = move(d);
                return "translate(" + coords.x + "," + coords.y +")";
              });
             if (options.coords)
                  node.selectAll(".coords").text(function(d){
                    if (d.planet_id)
                      return "(x = "+(Math.round(d.x * 100) / 100)+ "; y = "+(Math.round(d.y * 100) / 100)+")";
                    return "";
                  });
            link
                .attr("x1", function(d) { return d.source.x; })
                .attr("y1", function(d) { return d.source.y; })
                .attr("x2", function(d) { return d.target.x; })
                .attr("y2", function(d) { return d.target.y; });
            force.alpha(0.1);
          });
      }
    },"json");
}

function visualize_univers()
{
  delete vis;
  delete force;
  $("#protovis").html("");

  var node, link, root;
  $.get('/board/datasUniverse', {width: width, height:height}, function(json){

      var force = d3.layout.force()
          .on("tick", tick)
          .charge(function(d) { return d._children ? -d.size / 100 : -30; })
          .linkDistance(function(d) { return d.target._children ? 80 : 30; })
          .size([width, height - 160]);

          vis = d3.select("#protovis").append("svg")
          .attr("width", width)
          .attr("height", height);

      root = json.univers;
      root.fixed = true;
      root.x = width / 2;
      root.y = height / 2 - 80;
      update();

    function update() {
      var nodes = flatten(root),
          links = d3.layout.tree().links(nodes);

      // Restart the force layout.
      force
          .nodes(nodes)
          .links(links)
          .start();

      // Update the links…
      link = vis.selectAll("line.link")
          .data(links, function(d) { return d.target.id; });

      // Enter any new links.
      link.enter().insert("svg:line", ".node")
          .attr("class", "link")
          .attr("x1", function(d) { return d.source.x; })
          .attr("y1", function(d) { return d.source.y; })
          .attr("x2", function(d) { return d.target.x; })
          .attr("y2", function(d) { return d.target.y; });

      // Exit any old links.
      link.exit().remove();

      // Update the nodes…
      node = vis.selectAll("circle.node")
          .data(nodes, function(d) { return d.id; })
          .style("fill", color);

      node.transition()
          .attr("r", function(d) { return d.children ? 4.5 : Math.sqrt(d.size) / 10; });

      // Enter any new nodes.
      node.enter().append("svg:circle")
          .attr("class", "node")
          .attr("cx", function(d) { return d.x; })
          .attr("cy", function(d) { return d.y; })
          .attr("r", function(d) { return d.children ? 4.5 : Math.sqrt(d.size) / 10; })
          .style("fill", color)
          .on("click", click)
          .call(force.drag);

      // Exit any old nodes.
      node.exit().remove();
    }

    function tick() {
      link.attr("x1", function(d) { return d.source.x; })
          .attr("y1", function(d) { return d.source.y; })
          .attr("x2", function(d) { return d.target.x; })
          .attr("y2", function(d) { return d.target.y; });

      node.attr("cx", function(d) { return d.x; })
          .attr("cy", function(d) { return d.y; });
    }

    // Color leaf nodes orange, and packages white or blue.
    function color(d) {
      return d._children ? "#3182bd" : d.children ? "#c6dbef" : "#fd8d3c";
    }

    // Toggle children on click.
    function click(d) {
      if (d.children) {
        d._children = d.children;
        d.children = null;
      } else {
        d.children = d._children;
        d._children = null;
      }
      update();
    }

    // Returns a list of all nodes under the root.
    function flatten(root) {
      var nodes = [], i = 0;

      function recurse(node) {
        if (node.children) node.size = node.children.reduce(function(p, v) { return p + recurse(v); }, 0);
        if (!node.id) node.id = ++i;
        nodes.push(node);
        return node.size;
      }

      root.size = recurse(root);
      return nodes;
    }
  }, "json");
};

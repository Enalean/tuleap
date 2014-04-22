/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

var tuleap = tuleap || { };
tuleap.graphontrackersv5 = tuleap.graphontrackersv5 || { };
tuleap.graphontrackersv5.graphs = tuleap.graphontrackersv5.graphs || { };

// Inspired from http://bl.ocks.org/mbostock/3887193
tuleap.graphontrackersv5.drawDonut = function (id, graph) {
    var width             = graph.width,
        height            = graph.height,
        radius            = Math.min(width, height) / 2,
        inner_radius_coef = width/550,
        data              = [],
        total_values      = 0,
        color             = d3.scale.category20c();

    for (var i = 0; i < graph.data.length; ++i) {
        total_values += parseFloat(graph.data[i]);
    }

    for (var i = 0; i < graph.data.length; ++i) {
        var c = graph.colors[i];
        if (c === null) {
            c = color(i);
        }

        var line = {
            "label": graph.legend[i],
            "value": graph.data[i],
            "percentage": ((graph.data[i] / total_values) * 100).toFixed(0),
            "color": c
        };

        data.push(line);
        graph.colors[i] = c;
    }

    var pie = d3.layout.pie()
        .value(function(d) { return d.value; })
        .sort(null);

    var arc = d3.svg.arc()
        .innerRadius((radius - 50) / 2)
        .outerRadius(radius - 50);

    var svg = d3.selectAll(".plugin_graphontrackersv5_chart[data-graph-id="+id+']').append("svg")
        .attr("width", width)
        .attr("height", height);

    var grads = svg.append("defs").selectAll("linearGradient")
            .data(pie(data))
        .enter()
            .append("linearGradient")
            .attr("x1", 0)
            .attr("y1", 0)
            .attr("x2", 0)
            .attr("y2", 1)
            .attr("id", function(d, i) { return getGradientId(i); });
    grads.append("stop").attr("offset", "0%").style("stop-color", function(d, i) { return d3.rgb(d.data.color).brighter(0.5); });
    grads.append("stop").attr("offset", "100%").style("stop-color", function(d, i) { return d.data.color; });

    var chart = svg.append("g")
        .attr("transform", "translate(" + width / 3 + "," + height / 2 + ")");

    var g = chart.selectAll(".arc")
          .data(pie(data))
        .enter().append("g")
          .attr("class", "arc");

    g.append("line")
        .attr("x1", 0)
        .attr("x2", 0)
        .attr("y1", -radius+50)
        .attr("y2", function(d, i) {
            if(i % 2 === 0) {
              return -radius+40;
            } else {
              return -radius+25;
            }
        })
        .attr("stroke", "#DDD")
        .attr("transform", function(d) {
            radius;
          return "rotate(" + (d.startAngle+d.endAngle)/2 * (180/Math.PI) + ")";
        });

    g.append("path")
        .attr("d", arc)
        .style("fill", function(d, i) { return "url(#" + getGradientId(i) + ")"; })
        .attr("class", function (d, i) {
            return getDonutSliceClass(i);
        })
        .on("mouseover", function(d, i) {
            svg.select("." + getLegendClass(i)).style("font-weight", "bold");
        })
        .on("mouseout", function(d, i) {
            svg.select("." + getLegendClass(i)).style("font-weight", "normal");
        })
        .transition()
            .duration(750)
            .attrTween('d', function (b) {
                var i = d3.interpolate(
                {
                    startAngle: 0,
                    endAngle: 0
                }, b);

                return function(t) { return arc(i(t)); };
            });

      g.append("text")
          .attr("transform", function(d) { return "translate(" + arc.centroid(d) + ")"; })
          .attr("transform", function(d, i) {
             var dist;
             if(i % 2 === 0) {
               dist = radius - 34;
             } else {
               dist = radius - 19;
             }
             var angle  = (d.startAngle + d.endAngle) / 2, // Middle of wedge
                 x      = dist * Math.sin(angle),
                 y      = -dist * Math.cos(angle);

             return "translate(" + x + "," + y + ")";
           })
          .attr("dy", ".35em")
          .style("text-anchor", function (d) {
              var angle  = (d.startAngle + d.endAngle) / 2;

              if (angle > Math.PI) {
                  return "end";
              }
              return "start";
           })
          .text(function(d) { return d.data.percentage+'%'; });

    var legend_x = 2 * width / 3 + 20;
    var legend_y = Math.max(0, height / 2 - 20 / 2 * data.length);
    var legend_group = svg.append("g")
        .attr("transform", "translate(" + legend_x + ", " + legend_y + ")");

    var legend = legend_group.selectAll(".legend")
        .data(data)
        .enter().append("g")
        .attr("class", "legend")
        .attr("transform", function(d, i) { return "translate(0, " + i * 20 + ")"; })
        .on("mouseover", function(d, i) {
            svg.select("." + getDonutSliceClass(i))
                .transition()
                .attr("transform", "scale(1.05)")
        })
        .on("mouseout", function(d, i) {
            svg.select("." + getDonutSliceClass(i))
                .transition()
                .attr("transform", "scale(1)");
        });

    var colors_range = d3.scale.ordinal().range(graph.colors);

    legend.append("rect")
        .attr("x", 0)
        .attr("rx", 3)
        .attr("ry", 3)
        .attr("width", 16)
        .attr("height", 16)
        .style("fill", function (d) { return d.color; });

    legend.append("text")
        .attr("class", function (d, i) { return getLegendClass(i); })
        .attr("x", 22)
        .attr("y", 8)
        .attr("dy", ".35em")
        .style("text-anchor", "start")
        .text(function(d) {
            var legend = d.label,
                length = legend.length;

            if (length > 25) {
                return legend.substr(0, 15) + '…' + legend.substr(length - 10, length);
            }
            return legend;
        });


    function getGradientId(value_index) {
        return 'grad_' + id + '_' + value_index;
    }

    function getLegendClass(value_index) {
        return 'legend_' + id + '_' + value_index;
    }

    function getDonutSliceClass(value_index) {
        return 'slice_' + id + '_' + value_index;
    }

};

!function ($) {
    $(document).ready(function () {
        $.each(tuleap.graphontrackersv5.graphs, function (id, graph) {
            if (graph.type === 'pie') {
                tuleap.graphontrackersv5.drawDonut(id, graph);
            }
        });
    });
}(window.jQuery);
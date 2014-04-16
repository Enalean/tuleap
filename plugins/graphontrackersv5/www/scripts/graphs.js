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

// Inspired from http://bl.ocks.org/mbostock/3887193
tuleap.graphontrackersv5.drawDonut = function (id, graph) {
    var width        = graph.width,
        height       = graph.height,
        radius       = Math.min(width, height) / 2,
        data         = [],
        total_values = 0,
        color        = d3.scale.category20();

    for (var i = 0; i < graph.data.length; ++i) {
        total_values += parseFloat(graph.data[i]);
    }

    for (var i = 0; i < graph.data.length; ++i) {
        var c = graph.colors[i];
        if (c === null) {
            c = color(i);
        }
        data.push({
            "label": graph.legend[i],
            "value": graph.data[i],
            "percentage": ((graph.data[i] / total_values) * 100).toFixed(1),
            "color": c
        });
    }

    var pie = d3.layout.pie()
        .value(function(d) { return d.value; })
        .sort(null);

    var arc = d3.svg.arc()
        .innerRadius(radius - 125)
        .outerRadius(radius - 50);

    var svg = d3.select("#plugin_graphontrackersv5_chart_"+id).append("svg")
        .attr("width", width)
        .attr("height", height)
      .append("g")
        .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

    var g = svg.selectAll(".arc")
          .data(pie(data))
        .enter().append("g")
          .attr("class", "arc");

      g.append("path")
          .attr("d", arc)
          .style("fill", function(d) { return d.data.color; });

      g.append("text")
          .attr("transform", function(d) { return "translate(" + arc.centroid(d) + ")"; })
          .attr("dy", ".35em")
          .style("text-anchor", "middle")
          .text(function(d) { return d.data.label+' ('+d.data.percentage+'%)'; });
};

! function ($) {
    $(document).ready(function () {
        $.each(tuleap.graphontrackersv5.graphs, function (id, graph) {
            if (graph.type === 'pie') {
                tuleap.graphontrackersv5.drawDonut(id, graph);
            }
        });
    });
} (jQuery);
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
tuleap.graphontrackersv5.draw = {};

tuleap.graphontrackersv5.topRoundedRect = function(x, y, width, height, radius) {
    return "M" + (x + radius) + "," + y
        + "h" + (width - 2 * radius)
        + "a" + radius + "," + radius + " 0 0 1 " + radius + "," + radius
        + "v" + (height - radius)
        + "h" + (- width)
        + "v" + (- height + radius)
        + "a" + radius + "," + radius + " 0 0 1 " + radius + "," + -radius
        + "z";
};

/**
 * Utility function to display x axis legend labels with alternate height so
 * we can read the labels as they no longer overlap
 *
 * Not inspired by bl.ocks \o/
 *
 * @param {Object} svg
 * @param {int} height
 * @param {Object} xAxis
 */
tuleap.graphontrackersv5.alternateXAxisLabels = function (svg, height, xAxis) {
    var x_labels_alternate_delta = 15;
    var x_labels = svg.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    x_labels.selectAll("text")
        .attr("transform", function (d, i) {
            var y_delta = 0;
            if (i % 2 === 0) {
                y_delta += x_labels_alternate_delta;
            }
            return "translate(0," + y_delta + ")";
        });

    x_labels.selectAll("line")
        .attr("x2", 0)
        .attr("y2", function (d, i) {
            var y_delta = 6;
            if (i % 2 === 0) {
                y_delta += x_labels_alternate_delta;
            }
            return y_delta;
        });
};
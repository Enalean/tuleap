/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
var tuleap    = tuleap || {};
tuleap.charts = tuleap.charts || {};

tuleap.charts.statisticsPieChart = function (chart) {
    var width,
        height,
        radius,
        data,
        g,
        svg,
        pie,
        arc,
        arc_text,
        div_graph,
        prefix,
        general_prefix;

    chart.prefix = function (new_prefix) {
        if (!arguments.length) {
            return prefix;
        }
        prefix = new_prefix;
        return chart;
    };

    chart.generalPrefix = function (new_prefix) {
        if (!arguments.length) {
            return general_prefix;
        }
        general_prefix = new_prefix;
        return chart;
    };

    chart.divGraph = function (new_div_graph) {
        if (!arguments.length) {
            return div_graph;
        }
        div_graph = new_div_graph;
        return chart;
    };

    chart.svg = function (new_svg) {
        if (!arguments.length) {
            return svg;
        }
        svg = new_svg;
        return chart;
    };

    chart.g = function (new_g) {
        if (!arguments.length) {
            return g;
        }
        g = new_g;
        return chart;
    };

    chart.arc = function (new_arc) {
        if (!arguments.length) {
            return arc;
        }
        arc = new_arc;
        return chart;
    };

    chart.arcText = function (new_arc_text) {
        if (!arguments.length) {
            return arc_text;
        }
        arc_text = new_arc_text;
        return chart;
    };

    chart.arcOver = function (new_arc_over) {
        if (!arguments.length) {
            return arc_over;
        }
        arc_over = new_arc_over;
        return chart;
    };

    chart.arcOverText = function (new_arc_over_text) {
        if (!arguments.length) {
            return arc_over_text;
        }
        arc_over_text = new_arc_over_text;
        return chart;
    };

    chart.pie = function (new_pie) {
        if (!arguments.length) {
            return pie;
        }
        pie = new_pie;
        return chart;
    };

    chart.data = function (new_data) {
        if (!arguments.length) {
            return data;
        }
        data = new_data;
        return chart;
    };

    chart.width = function (new_width) {
        if (!arguments.length) {
            return width;
        }
        width = new_width;
        return chart;
    };

    chart.height = function (new_height) {
        if (!arguments.length) {
            return height;
        }
        height = new_height;
        return chart;
    };

    chart.radius = function (new_radius) {
        if (!arguments.length) {
            return radius;
        }
        radius = new_radius;
        return chart;
    };
};
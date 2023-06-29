/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
export default function (chart) {
    var div_graph,
        width,
        height,
        margin,
        data,
        g,
        svg,
        stack,
        line,
        area,
        graph,
        color_scale,
        y_max,
        x_axis,
        y_axis,
        x_scale,
        y_scale,
        columns,
        stack_data,
        keys,
        tooltip,
        bisect_date,
        localized_format,
        legend_text;

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

    chart.stack = function (new_stack) {
        if (!arguments.length) {
            return stack;
        }
        stack = new_stack;
        return chart;
    };

    chart.area = function (new_area) {
        if (!arguments.length) {
            return area;
        }
        area = new_area;
        return chart;
    };

    chart.line = function (new_line) {
        if (!arguments.length) {
            return line;
        }
        line = new_line;
        return chart;
    };

    chart.graph = function (new_graph) {
        if (!arguments.length) {
            return graph;
        }
        graph = new_graph;
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

    chart.margin = function (new_margin) {
        if (!arguments.length) {
            return margin;
        }
        margin = new_margin;
        return chart;
    };

    chart.colorScale = function (new_color_scale) {
        if (!arguments.length) {
            return color_scale;
        }
        color_scale = new_color_scale;
        return chart;
    };

    chart.yMax = function (new_y_max) {
        if (!arguments.length) {
            return y_max;
        }
        y_max = new_y_max;
        return chart;
    };

    chart.xAxis = function (new_x_axis) {
        if (!arguments.length) {
            return x_axis;
        }
        x_axis = new_x_axis;
        return chart;
    };

    chart.yAxis = function (new_y_axis) {
        if (!arguments.length) {
            return y_axis;
        }
        y_axis = new_y_axis;
        return chart;
    };

    chart.xScale = function (new_x_scale) {
        if (!arguments.length) {
            return x_scale;
        }
        x_scale = new_x_scale;
        return chart;
    };

    chart.yScale = function (new_y_scale) {
        if (!arguments.length) {
            return y_scale;
        }
        y_scale = new_y_scale;
        return chart;
    };

    chart.columns = function (new_columns) {
        if (!arguments.length) {
            return columns;
        }
        columns = new_columns;
        return chart;
    };

    chart.data = function (new_data) {
        if (!arguments.length) {
            return data;
        }
        data = new_data;
        return chart;
    };

    chart.stackData = function (new_stack_data) {
        if (!arguments.length) {
            return stack_data;
        }
        stack_data = new_stack_data;
        return chart;
    };

    chart.keys = function (new_keys) {
        if (!arguments.length) {
            return keys;
        }
        keys = new_keys;
        return chart;
    };

    chart.tooltip = function (new_tooltip) {
        if (!arguments.length) {
            return tooltip;
        }
        tooltip = new_tooltip;
        return chart;
    };

    chart.bisectDate = function (new_bisect_date) {
        if (!arguments.length) {
            return bisect_date;
        }
        bisect_date = new_bisect_date;
        return chart;
    };

    chart.localizedFormat = function (new_localized_format) {
        if (!arguments.length) {
            return localized_format;
        }
        localized_format = new_localized_format;
        return chart;
    };

    chart.legendText = function (new_legend_text) {
        if (!arguments.length) {
            return legend_text;
        }
        legend_text = new_legend_text;
        return chart;
    };
}

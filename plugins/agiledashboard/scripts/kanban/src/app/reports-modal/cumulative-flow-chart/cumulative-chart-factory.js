/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import cumulativeFlowChart from "./cumulative-chart.js";
import moment from "moment";
/* eslint-disable-next-line you-dont-need-lodash-underscore/map, you-dont-need-lodash-underscore/find, you-dont-need-lodash-underscore/for-each, you-dont-need-lodash-underscore/reduce */
import { map, find, forEach, defaults, reduce } from "lodash-es";
import { pointer, select, selectAll } from "d3-selection";
import { bisector, extent, max } from "d3-array";
import { scaleOrdinal, scaleTime, scaleLinear } from "d3-scale";
import { axisBottom, axisLeft } from "d3-axis";
import { stackOffsetNone, stackOrderNone, stack, area } from "d3-shape";

export default (options = {}) => {
    let chart = {};

    cumulativeFlowChart(chart);

    chart.width(options.width);
    chart.height(options.height);
    chart.margin(options.margin);
    chart.data(options.data);
    chart.legendText(options.legend_text);
    chart.localizedFormat(options.localized_format);
    chart.divGraph(select("#" + options.graph_id));

    chart.init = () => {
        chart.bisectDate(
            bisector((d) => {
                return moment(d.date).valueOf();
            }).left
        );

        chart.svg(
            chart
                .divGraph()
                .append("svg")
                .attr("width", chart.width() + chart.margin().left + chart.margin().right)
                .attr("height", chart.height() + chart.margin().top + chart.margin().bottom)
        );

        chart.g(
            chart
                .svg()
                .append("g")
                .attr(
                    "transform",
                    "translate(" + chart.margin().left + "," + chart.margin().top + ")"
                )
        );

        chart.initData();
        chart.initX();
        chart.initYMax();
        chart.initY();
        chart.initColor();
        chart.initStack();
        chart.initArea();
        chart.initGraph();
        chart.initLegend();
        chart.initTooltip();
        chart.initAreaEvents();
        chart.initLegendEvents();
    };

    chart.initData = () => {
        const stack_data = parseData(chart.data());
        chart.stackData(stack_data);
        chart.columns(chart.data());

        const keys = map(chart.data(), (column) => {
            return column.id;
        });

        chart.keys(keys);
    };

    chart.initColor = () => {
        const schemeCategory20cWithoutLightest = [
            "#3182bd",
            "#6baed6",
            "#9ecae1",
            "#e6550d",
            "#fd8d3c",
            "#fdae6b",
            "#31a354",
            "#74c476",
            "#a1d99b",
            "#756bb1",
            "#9e9ac8",
            "#bcbddc",
            "#636363",
            "#969696",
            "#bdbdbd",
        ];

        const color_scale = scaleOrdinal().range(schemeCategory20cWithoutLightest);

        chart.colorScale(color_scale);

        const color_domain = chart.columns().map((data, index, columns) => {
            return columns.length - 1 - index;
        });
        chart.colorScale().domain(color_domain);
    };

    chart.initX = () => {
        const first_column = chart.columns()[0];

        const time_scale_extent = extent(first_column.values, (d) => {
            return moment(d.start_date).toDate();
        });

        const x_scale = scaleTime().domain(time_scale_extent).range([0, chart.width()]);

        chart.xScale(x_scale);

        const x_axis = axisBottom()
            .scale(x_scale)
            .tickFormat((d) => {
                return moment(d).format(chart.localizedFormat());
            });

        if (chart.getXAxisTicks(chart.width()) > 0) {
            x_axis.ticks(chart.getXAxisTicks(chart.width()));
        }

        chart.xAxis(x_axis);
    };

    chart.initY = () => {
        const y_scale = scaleLinear().domain([0, chart.yMax()]).range([chart.height(), 0]);

        chart.yScale(y_scale);

        const y_axis = axisLeft().scale(y_scale);

        if (chart.getYAxisTicks(chart.height()) > 0) {
            y_axis.ticks(chart.getYAxisTicks(chart.height()));
        }

        chart.yAxis(y_axis);
    };

    chart.initYMax = () => {
        const first_column = chart.columns()[0];

        const max_kanban_items_count = max(first_column.values, function (data_point, index) {
            return sumKanbanItemsCountsForOneDay(index);
        });

        chart.yMax(max_kanban_items_count);

        function sumKanbanItemsCountsForOneDay(day_index) {
            const columns_activated = chart.columns().filter((column) => column.activated === true);

            return columns_activated.reduce((previous_sum, current_column) => {
                return previous_sum + current_column.values[day_index].kanban_items_count;
            }, 0);
        }
    };

    chart.initGraph = () => {
        chart.drawAxis();
        chart.drawArea();
        chart.drawGuideLine();
        chart.updateGrid();
    };

    chart.initAreaEvents = () => {
        chart
            .g()
            .selectAll(".area")
            .on("mouseover", (event, d) => {
                select("#area_" + d.key).classed("hover", true);
                const column = find(chart.columns(), { id: d.key });
                if (column) {
                    column.hover = true;
                }
            })
            .on("mousemove", function (event) {
                const data_set = getDataSet(pointer(event)[0]);

                if (data_set && total(data_set) > 0) {
                    chart
                        .g()
                        .select(".guide-line")
                        .attr("x1", chart.xScale()(moment(data_set.date).toDate()))
                        .attr("y1", chart.height())
                        .attr("x2", chart.xScale()(moment(data_set.date).toDate()))
                        .attr("y2", 0)
                        .classed("guide-line-displayed", true)
                        .classed("guide-line-undisplayed", false);

                    chart
                        .tooltip()
                        .classed("tooltip-displayed", true)
                        .classed("tooltip-undisplayed", false);

                    const position = getTooltipPosition(pointer(event)[0], pointer(event)[1]);

                    chart
                        .tooltip()
                        .html(constructTooltipContent(data_set))
                        .style("left", position.left + "px")
                        .style("top", position.top + "px");
                }
            })
            .on("mouseout", (event, d) => {
                if (
                    event.relatedTarget &&
                    event.relatedTarget.nodeName !== "line" &&
                    event.relatedTarget.id !== "tooltip_" + d.key &&
                    event.relatedTarget.id !== "area_" + d.key
                ) {
                    select("#area_" + d.key).classed("hover", false);
                    const column = find(chart.columns(), { id: d.key });
                    if (column) {
                        column.hover = false;
                    }

                    chart
                        .tooltip()
                        .classed("tooltip-displayed", false)
                        .classed("tooltip-undisplayed", true);

                    chart
                        .g()
                        .select(".guide-line")
                        .classed("guide-line-displayed", false)
                        .classed("guide-line-undisplayed", true);
                }
            });

        function getTooltipPosition(mouse_x, mouse_y) {
            const position = {
                left: mouse_x + 100,
                top: mouse_y - 50,
            };

            const first_x_date = chart.stackData()[0].date;
            const last_x_date = chart.stackData()[chart.stackData().length - 1].date;
            const position_x_first_date = chart.xScale()(moment(first_x_date).toDate());
            const position_x_last_date = chart.xScale()(moment(last_x_date).toDate());
            const tooltip_width = select("#tooltip").node().getBoundingClientRect().width;

            if (
                position.left + tooltip_width >= position_x_last_date &&
                mouse_x - tooltip_width >= position_x_first_date
            ) {
                position.left = mouse_x - tooltip_width;
            }

            return position;
        }

        function getDataSet(coordinate_x) {
            const x_value = chart.xScale().invert(coordinate_x),
                index = chart.bisectDate()(chart.stackData(), moment(x_value).valueOf()),
                data_set_min = chart.stackData()[index - 1],
                data_set_max = chart.stackData()[index];
            let data_set_min_diff = 0,
                data_set_max_diff = 0;

            if (data_set_min) {
                data_set_min_diff = moment(x_value).diff(moment(data_set_min.date));
            }

            if (data_set_max) {
                data_set_max_diff = moment(data_set_max.date).diff(moment(x_value));
            }

            return data_set_min_diff > data_set_max_diff ? data_set_max : data_set_min;
        }

        function constructTooltipContent(data) {
            let tooltip = select(document.createElement("div")).attr("class", "tooltip-content");

            tooltip
                .append("div")
                .attr("class", "row-date")
                .text(moment(data.date).format(chart.localizedFormat()));

            const tooltip_content_row = tooltip
                .selectAll(".tooltip-content-row")
                .data(chart.columns().filter((column) => column.activated === true))
                .enter()
                .append("div")
                .attr("id", (d) => {
                    return "tooltip_" + d.id;
                })
                .attr("class", "tooltip-content-row")
                .classed("hover", (d) => {
                    return d.hover;
                });

            tooltip_content_row
                .append("div")
                .attr("class", "row-legend")
                .style("background-color", (d) => {
                    const index = chart.columns().findIndex((column) => column.id === d.id);
                    return chart.colorScale()(chart.columns().length - 1 - index);
                });

            tooltip_content_row
                .append("div")
                .attr("class", "row-label")
                .text((d) => {
                    return d.label;
                });

            tooltip_content_row
                .append("div")
                .attr("class", "row-value")
                .text((d) => {
                    return data[d.id];
                });

            const tooltip_content_total = tooltip
                .append("div")
                .attr("class", "tooltip-content-row");

            tooltip_content_total
                .append("div")
                .attr("class", "row-legend")
                .style("background-color", "#FFFFFF");

            tooltip_content_total
                .append("div")
                .attr("class", "row-label row-total-label")
                .text("Total");

            tooltip_content_total
                .append("div")
                .attr("class", "row-value row-total-value")
                .text(total(data));

            return tooltip.node().outerHTML;
        }
    };

    chart.initLegendEvents = () => {
        selectAll(".legend-value")
            .on("click", function (event, d) {
                updateLegend(d, select(this));
                chart.redraw();
            })
            .on("mouseover", (event, d) => {
                select("#area_" + d.id).classed("hover", true);
            })
            .on("mouseout", (event, d) => {
                select("#area_" + d.id).classed("hover", false);
            });
    };

    chart.initStack = () => {
        const d3_stack = stack().keys(chart.keys()).order(stackOrderNone).offset(stackOffsetNone);

        chart.stack(d3_stack);
    };

    chart.initArea = () => {
        const d3_area = area()
            .x((d) => {
                return chart.xScale()(moment(d.data.date).toDate());
            })
            .y0((d) => {
                return chart.yScale()(d[0]);
            })
            .y1((d) => {
                return chart.yScale()(d[1]);
            });

        chart.area(d3_area);
    };

    chart.initLegend = () => {
        const svg_legend = chart.divGraph().append("div").attr("id", "legend").append("ul");

        const legend = svg_legend
            .selectAll(".legend-value")
            .data(chart.columns().reverse())
            .enter()
            .append("li")
            .attr("id", (d) => {
                return "legend_" + d.id;
            })
            .attr("class", "legend-value")
            .style("text-decoration", (d) => {
                if (d.activated) {
                    return "none";
                }

                return "line-through";
            });

        legend
            .append("span")
            .attr("class", "legend-value-color")
            .style("background-color", (d, i) => {
                return chart.colorScale()(chart.columns().length - 1 - i);
            });

        legend.append("span").text((d) => {
            return d.label;
        });
    };

    chart.initTooltip = () => {
        const tooltip = chart
            .divGraph()
            .append("div")
            .attr("id", "tooltip")
            .classed("tooltip-displayed", false)
            .classed("tooltip-undisplayed", true);

        chart.tooltip(tooltip);
    };

    chart.drawAxis = () => {
        chart
            .g()
            .append("g")
            .attr("class", "axis x-axis")
            .attr("transform", "translate(0, " + chart.height() + ")")
            .call(chart.xAxis());

        chart.g().append("g").attr("class", "axis y-axis").call(chart.yAxis());

        chart
            .g()
            .selectAll(".y-axis")
            .append("text")
            .attr("class", "y-axis-label")
            .attr("text-anchor", "middle")
            .attr("transform", "translate(-35," + chart.height() / 2 + ")rotate(-90)")
            .text(chart.legendText());
    };

    chart.drawArea = () => {
        chart
            .g()
            .selectAll(".area")
            .data(chart.stack()(chart.stackData()))
            .enter()
            .append("path")
            .attr("id", (d) => {
                return "area_" + d.key;
            })
            .attr("class", "area")
            .attr("fill", (d, i) => {
                return chart.colorScale()(i);
            })
            .attr("d", chart.area());
    };

    chart.drawGuideLine = () => {
        chart
            .g()
            .append("line")
            .attr("class", "guide-line")
            .classed("guide-line-displayed", false)
            .classed("guide-line-undisplayed", true);
    };

    chart.updateGrid = () => {
        chart.g().selectAll(".x-axis .tick line").attr("y2", -chart.height());

        /* eslint-disable */
        chart
            .g()
            .selectAll(".y-axis .tick line")
            .attr("x2", (d, i, lines) => {
                if (i < lines.length - 1) {
                    return chart.width();
                }
            });
        /* eslint-enable */

        chart.g().selectAll(".x-axis .tick").attr("class", "tick grid");

        chart
            .g()
            .selectAll(".y-axis .tick")
            .attr("class", (d, i) => {
                if (i > 0) {
                    return "tick grid";
                }
                return "tick";
            });
    };

    chart.getXAxisTicks = (size) => {
        let ticks = 0;

        if (size <= 320) {
            ticks = 3;
        } else if (size <= 480) {
            ticks = 5;
        } else if (size <= 768) {
            ticks = 7;
        }

        return ticks;
    };

    chart.getYAxisTicks = (size) => {
        let ticks = 0;

        if (size <= 320) {
            ticks = 5;
        } else if (size <= 480) {
            ticks = 7;
        } else if (size <= 768) {
            ticks = 9;
        }

        return ticks;
    };

    chart.resize = function (height, width) {
        if (arguments.length) {
            chart.height(height);
            chart.width(width);
        }
        return chart;
    };

    chart.redraw = () => {
        chart.initData(chart.columns());
        chart.initYMax();

        chart.xScale().range([0, chart.width()]);
        chart.yScale().range([chart.height(), 0]);

        chart.yScale().domain([0, chart.yMax()]);

        chart
            .g()
            .selectAll(".x-axis")
            .attr("transform", "translate(0, " + chart.height() + ")")
            .call(chart.xAxis());

        chart.g().selectAll(".y-axis").call(chart.yAxis());

        chart.g().selectAll(".area").data(chart.stack()(chart.stackData())).attr("d", chart.area());

        chart
            .g()
            .selectAll(".y-axis-label")
            .attr("transform", "translate(-35," + chart.height() / 2 + ")rotate(-90)");

        chart.updateGrid();
        if (chart.getXAxisTicks(chart.width()) > 0) {
            chart.xAxis().ticks(chart.getXAxisTicks(chart.width()));
        }

        if (chart.getYAxisTicks(chart.height()) > 0) {
            chart.yAxis().ticks(chart.getYAxisTicks(chart.height()));
        }
    };

    return chart;
};

function parseData(data) {
    const parsed_data = [];
    forEach(data, function (column) {
        defaults(column, { activated: true });

        forEach(column.values, function (value, value_index) {
            if (!parsed_data[value_index]) {
                parsed_data[value_index] = {};
            }

            if (column.activated) {
                parsed_data[value_index][column.id] = value.kanban_items_count;
            } else {
                parsed_data[value_index][column.id] = 0;
            }
            parsed_data[value_index].date = value.start_date;
        });
    });

    return parsed_data;
}

function total(data) {
    return reduce(
        data,
        function (sum, value) {
            return !isNaN(value) ? sum + value : sum;
        },
        0
    );
}

function updateLegend(d3_column_data, d3_legend_element) {
    if (d3_column_data.activated) {
        d3_column_data.activated = false;
        d3_legend_element.style("text-decoration", "line-through");
    } else {
        d3_column_data.activated = true;
        d3_legend_element.style("text-decoration", "none");
    }
}

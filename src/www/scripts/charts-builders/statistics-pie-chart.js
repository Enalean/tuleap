/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { select } from "d3-selection";
import { arc, pie } from "d3-shape";

import "d3-transition";

export class StatisticsPieChart {
    constructor({ id, width, height, radius, data, prefix, general_prefix }) {
        Object.assign(this, {
            id,
            width,
            height,
            radius,
            data,
            prefix,
            general_prefix,
            div_graph: select("#" + id),
            group: null,
            svg: null,
            arc_text: null,
        });
    }

    init() {
        this.svg = this.div_graph
            .append("svg")
            .attr("width", this.width)
            .attr("height", this.height);

        this.group = this.svg
            .append("g")
            .attr("transform", "translate(" + this.width / 2 + "," + this.height / 2 + ")");

        this.initArc();
        this.initPie();
        this.initGraph();
        this.initLegend();
        this.initText();
    }

    initArc() {
        this.arc = arc()
            .innerRadius(this.radius / 4.5)
            .outerRadius(this.radius / 3);

        this.arc_text = arc()
            .innerRadius(this.radius / 3)
            .outerRadius(this.radius / 3 + 20);

        this.arc_over = arc()
            .innerRadius(this.radius / 4.25)
            .outerRadius(this.radius / 2.75)
            .padAngle((slice_data) => (sliceEqualsTo180Degrees(slice_data) ? 0 : 0.05));

        this.arc_over_text = arc()
            .innerRadius(this.radius / 2.75)
            .outerRadius(this.radius / 2.75 + 20);
    }

    initPie() {
        this.pie = pie()
            .value(function (d) {
                return d.count;
            })
            .sort(null);
    }

    initLegend() {
        const svg_legend = select("#" + this.id)
            .append("div")
            .attr("id", this.getLegendClass())
            .append("ul")
            .attr("class", this.getLegendGeneralClass());

        const legend = svg_legend
            .selectAll("." + this.getLegendClass())
            .data(this.data)
            .enter()
            .append("li")
            .attr("class", (d) => {
                return this.getLegendClass() + " " + this.getLegendClassByKey(d.key);
            });

        legend.append("span").attr("class", (d) => {
            return this.getLegendColorSpanClass() + " " + this.getLegendColorClassByKey(d.key);
        });

        legend
            .append("span")
            .attr("class", this.getLegendTextSpanClass())
            .text(function (d) {
                return d.label;
            });

        legend
            .on("mouseover", (d) => {
                this.onSliceAndTextOver(d.key);
                this.replaceText(this.group.select("." + this.getSliceClassByKey(d.key)).datum());
            })
            .on("mouseout", (d) => {
                this.onSliceAndTextOut(d.key);
            });

        legend.each(function () {
            const li_width = select(this).node().getBoundingClientRect().width;
            select(this).style("width", li_width + 10 + "px");
        });
    }

    initGraph() {
        const arc_elements = this.group
            .selectAll("." + this.getSliceClass())
            .data(this.pie(this.data))
            .enter()
            .append("g")
            .attr("class", (d) => {
                return this.getSliceClass() + " " + this.getSliceClassByKey(d.data.key);
            });

        arc_elements.append("path").attr("class", this.getSlicePathClass()).attr("d", this.arc);

        arc_elements
            .append("text")
            .attr("class", this.getSliceTextClass())
            .attr("transform", (d) => {
                return "translate(" + this.arc_text.centroid(d) + ")";
            })
            .attr("dy", ".35em")
            .text((d) => {
                if (d.data.value && d.data.value.length > 0) {
                    return d.data.value;
                }

                if (d.value > 0) {
                    return d.value;
                }

                return "";
            });

        arc_elements
            .on("mouseover", (d) => {
                this.onSliceAndTextOver(d.data.key);
                this.replaceText(d);
            })
            .on("mouseout", (d) => {
                this.onSliceAndTextOut(d.data.key);
            });
    }

    initText() {
        const slices = this.svg.selectAll("." + this.getSliceClass());

        slices.each(function (d) {
            const angle = (d.startAngle + d.endAngle) / 2;

            if (angle > Math.PI) {
                select(this).select("text").style("text-anchor", "end");
            } else {
                select(this).select("text").style("text-anchor", "start");
            }
        });

        slices.each((d) => {
            this.displayText(d);
        });
    }

    redraw({ width, height, radius }) {
        Object.assign(this, {
            width,
            height,
            radius,
        });

        this.svg.attr("width", this.width).attr("height", this.height);

        this.group.attr("transform", "translate(" + this.width / 2 + "," + this.height / 2 + ")");

        this.initArc();

        this.group.selectAll("path").attr("d", this.arc);

        this.group.selectAll("text").attr("transform", (d) => {
            return "translate(" + this.arc_text.centroid(d) + ")";
        });

        this.initText();
    }

    getSliceClass() {
        return this.prefix + "-slice";
    }

    getSliceClassByKey(value_key) {
        return this.prefix + "-slice-" + value_key;
    }

    getSlicePathClass() {
        return this.prefix + "-slice-path";
    }

    getSliceTextClass() {
        return this.prefix + "-slice-text";
    }

    getSliceTextUndisplayedClass() {
        return this.prefix + "-slice-text-undisplayed";
    }

    getLegendGeneralClass() {
        return this.general_prefix + "-legend";
    }

    getLegendClass() {
        return this.prefix + "-legend";
    }

    getLegendClassByKey(value_key) {
        return this.prefix + "-legend-" + value_key;
    }

    getLegendColorClassByKey(value_key) {
        return this.prefix + "-legend-color-" + value_key;
    }

    getLegendColorSpanClass() {
        return this.prefix + "-legend-color-span";
    }

    getLegendTextSpanClass() {
        return this.prefix + "-legend-text-span";
    }

    getLegendSelectedClass() {
        return this.prefix + "-legend-selected";
    }

    displayText(arc_data) {
        const angle = (arc_data.startAngle + arc_data.endAngle) / 2;

        const arc_element = this.group.select("." + this.getSliceClassByKey(arc_data.data.key));

        const text_element_client = arc_element.select("text").node().getBoundingClientRect();
        const text_element_width = text_element_client.width;
        const text_element_left = text_element_client.left;
        const text_element_right = text_element_client.right;

        const svg_element_client = this.svg.node().getBoundingClientRect();
        const svg_element_left = svg_element_client.left;
        const svg_element_right = svg_element_client.right;

        const path_width = arc_element.select("path").node().getBoundingClientRect().width;
        const path_height = arc_element.select("path").node().getBoundingClientRect().height;

        if (path_width < text_element_width || path_height < text_element_width) {
            arc_data.displayed = false;
            arc_element.select("text").classed(this.getSliceTextUndisplayedClass(), true);
        } else {
            arc_data.displayed = true;
        }

        if (angle > Math.PI) {
            if (text_element_left < svg_element_left && arc_data.displayed) {
                arc_data.displayed = false;
                arc_element.select("text").classed(this.getSliceTextUndisplayedClass(), true);
            } else if (arc_data.displayed) {
                arc_data.displayed = true;
                arc_element.select("text").classed(this.getSliceTextUndisplayedClass(), false);
            }
        } else {
            if (text_element_right > svg_element_right && arc_data.displayed) {
                arc_data.displayed = false;
                arc_element.select("text").classed(this.getSliceTextUndisplayedClass(), true);
            } else if (arc_data.displayed) {
                arc_data.displayed = true;
                arc_element.select("text").classed(this.getSliceTextUndisplayedClass(), false);
            }
        }
    }

    replaceText(arc_data) {
        const arc_element = this.group.select("." + this.getSliceClassByKey(arc_data.data.key));
        const angle = (arc_data.startAngle + arc_data.endAngle) / 2;

        const text_element_client = arc_element.select("text").node().getBoundingClientRect();
        const text_element_left = text_element_client.left;
        const text_element_right = text_element_client.right;

        const svg_element_client = this.svg.node().getBoundingClientRect();
        const svg_element_left = svg_element_client.left;
        const svg_element_right = svg_element_client.right;

        if (angle > Math.PI) {
            if (text_element_left < svg_element_left) {
                arc_element.select("text").style("text-anchor", "start");
            }
        } else {
            if (text_element_right > svg_element_right) {
                arc_element.select("text").style("text-anchor", "end");
            }
        }
    }

    onSliceAndTextOver(key) {
        this.group
            .select("." + this.getSliceClassByKey(key) + " path")
            .transition()
            .attr("d", this.arc_over)
            .attr("transform", (d) => {
                if (sliceEqualsTo180Degrees(d)) {
                    const angle = d.startAngle + d.endAngle / 2;

                    if (angle > Math.PI) {
                        return "translate(-2,2)";
                    }
                    return "translate(2,2)";
                }

                return "";
            });

        this.group
            .select("." + this.getSliceClassByKey(key) + " text")
            .classed(this.getSliceTextUndisplayedClass(), false)
            .transition()
            .attr("transform", (d) => {
                return "translate(" + this.arc_over_text.centroid(d) + ")";
            });

        this.div_graph
            .select("." + this.getLegendClassByKey(key))
            .classed(this.getLegendSelectedClass(), true);
    }

    onSliceAndTextOut(key) {
        this.group
            .select("." + this.getSliceClassByKey(key) + " path")
            .transition()
            .attr("d", this.arc)
            .attr("transform", (d) => {
                if (sliceEqualsTo180Degrees(d)) {
                    return "translate(0,0)";
                }

                return "";
            });

        this.group
            .select("." + this.getSliceClassByKey(key) + " text")
            .classed(this.getSliceTextUndisplayedClass(), (d) => !d.displayed)
            .transition()
            .attr("transform", (d) => {
                return "translate(" + this.arc_text.centroid(d) + ")";
            });

        this.div_graph
            .select("." + this.getLegendClassByKey(key))
            .classed(this.getLegendSelectedClass(), false);
    }
}

function sliceEqualsTo180Degrees(slice_data) {
    return (
        (slice_data.startAngle === 0 &&
            parseInt(slice_data.endAngle, 10) === parseInt(Math.PI, 10)) ||
        (parseInt(slice_data.startAngle, 10) === parseInt(Math.PI, 10) &&
            parseInt(slice_data.endAngle, 10) === parseInt(2 * Math.PI, 10))
    );
}

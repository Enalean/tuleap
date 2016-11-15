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
var tuleap = tuleap || {};
tuleap.admin = tuleap.admin || {};

tuleap.admin.statistictsPieChart =  function (options) {

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
        prefix;

    options = options || {};

    function chart() {
        chart.init(options);
    }

    chart.init = function (options) {
        chart.width(options.width);
        chart.height(options.height);
        chart.radius(options.radius);
        chart.data(options.data);
        chart.prefix(options.graph_id);
        chart.divGraph(d3.select('#' + options.graph_id));

        chart.svg(chart.divGraph().append('svg')
            .attr('width', chart.width())
            .attr('height', chart.height()));

        chart.g(chart.svg().append('g')
            .attr('transform', 'translate(' + (chart.width() / 2) + ',' + (chart.height() / 2) + ')'));


        chart.initArc();
        chart.initPie();
        chart.initGraph();
        chart.initLegend();
        chart.initText();
    };

    chart.initArc = function() {
        var arc = d3.arc()
            .innerRadius(chart.radius() / 4.5)
            .outerRadius(chart.radius() / 3);

        var arc_text = d3.arc()
            .innerRadius(chart.radius() / 3)
            .outerRadius(chart.radius() / 3 + 20);

        chart.arc(arc);
        chart.arcText(arc_text);
    };

    chart.initPie = function() {
        var pie = d3.pie()
            .value(function(d) { return d.count; })
            .sort(null);

        chart.pie(pie);
    };

    chart.initLegend = function() {
        var svg_legend = d3.select('#' + getGraphId()).append('div')
            .attr('id', getLegendClass())
            .append('ul');

        var legend = svg_legend.selectAll('.' + getLegendClass())
            .data(chart.data())
            .enter().append('li')
            .attr('class', function(d) { return getLegendClass() + ' ' + getLegendClassByKey(d.key); });

        legend.append('span')
            .attr('class', function(d) { return getLegendColorSpanClass() + ' ' + getLegendColorClassByKey(d.key); });

        legend.append('span')
            .attr('class', getLegendTextSpanClass())
            .text(function(d) { return d.label; });

        legend.on('mouseover', onOverValue)
            .on('mouseout', onOutValue);

        legend.each(function() {
            var li_width = d3.select(this).node().getBoundingClientRect().width;
            d3.select(this).style('width', li_width + 10 + 'px');
        });

        function onOverValue(d) {
            chart.g().select('.' + getSliceClassByKey(d.key) + ' path')
                .transition()
                .attr('transform', 'scale(1.05)');
            chart.g().select('.' + getSliceClassByKey(d.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), false);
            d3.select('.' + getLegendClassByKey(d.key)).classed(getLegendSelectedClass(), true);
            replaceText(chart.g().select('.' + getSliceClassByKey(d.key)).datum());
        }

        function onOutValue(d) {
            chart.g().select('.' + getSliceClassByKey(d.key) + ' path')
                .transition()
                .attr('transform', 'scale(1)');
            chart.g().select('.' + getSliceClassByKey(d.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), function(d) {
                    if (! d.displayed) {
                        return true;
                    }
                });
            d3.select('.' + getLegendClassByKey(d.key)).classed(getLegendSelectedClass(), false);
        }
    };

    chart.initGraph = function () {
        var arc_elements = chart.g().selectAll('.' + getSliceClass())
            .data(chart.pie()(chart.data()))
            .enter().append('g')
            .attr('class', function (d) { return  getSliceClass() + ' ' + getSliceClassByKey(d.data.key); });

        arc_elements.append('path')
            .attr('class', getSlicePathClass())
            .attr('d', chart.arc());

        arc_elements.append('text')
            .attr('class', getSliceTextClass())
            .attr('transform', function(d) {
                return 'translate(' + chart.arcText().centroid(d) + ')';
            })
            .attr('dy', '.35em')
            .text(function(d) {
                if (d.value > 0) {
                    return d.value;
                }
            });

        arc_elements.on('mouseover', onOverValue)
            .on('mouseout', onOutValue)
            .transition()
            .duration(750)
            .attrTween('d', function (b) {
                var i = d3.interpolate({startAngle: 0, endAngle: 0}, b);
                return function(t) { return chart.arc()(i(t)); };
            });

        function onOverValue(d) {
            chart.g().select('.' + getSliceClassByKey(d.data.key) + ' path')
                .transition()
                .attr('transform', 'scale(1.05)');
            chart.g().select('.' + getSliceClassByKey(d.data.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), false);
            d3.select('.' + getLegendClassByKey(d.data.key)).classed(getLegendSelectedClass(), true);
            replaceText(d);
        }

        function onOutValue(d) {
            chart.g().select('.' + getSliceClassByKey(d.data.key) + ' path')
                .transition()
                .attr('transform', 'scale(1)');
            chart.g().select('.' + getSliceClassByKey(d.data.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), function(d) {
                    if (! d.displayed) {
                        return true;
                    }
                });
            d3.select('.' + getLegendClassByKey(d.data.key)).classed(getLegendSelectedClass(), false);
        }
    };

    chart.initText = function () {
        d3.selectAll('.' + getSliceClass()).each(function (d) {
            var angle = (d.startAngle + d.endAngle) / 2;

            if (angle > Math.PI) {
                d3.select(this).select('text')
                    .style('text-anchor', 'end');
            } else {
                d3.select(this).select('text')
                    .style('text-anchor', 'start');
            }

            displayText(d);
        });
    };

    chart.redraw = function () {
        chart.svg().attr('width', chart.width())
            .attr('height', chart.height());

        chart.g()
            .attr('transform', 'translate(' + (chart.width()  / 2) + ',' + (chart.height() / 2) + ')');

        chart.initArc();

        chart.g().selectAll('path').attr('d', chart.arc());

        chart.g().selectAll('text').attr('transform', function(d) {
            return 'translate(' + (chart.arcText().centroid(d)) + ')';
        });

        chart.initText();
    };

    chart.prefix = function (new_prefix) {
        if (!arguments.length) {
            return prefix;
        }
        prefix = new_prefix;
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

    function getGraphId() {
        return chart.prefix();
    }

    function getSliceClass() {
        return chart.prefix() + '-slice';
    }

    function getSliceClassByKey(value_key) {
        return chart.prefix() + '-slice-' + value_key;
    }

    function getSlicePathClass() {
        return chart.prefix() + '-slice-path';
    }

    function getSliceTextClass() {
        return chart.prefix() + '-slice-text';
    }

    function getSliceTextUndisplayedClass() {
        return chart.prefix() + '-slice-text-undisplayed';
    }

    function getLegendClass() {
        return chart.prefix() + '-legend';
    }

    function getLegendClassByKey(value_key) {
        return chart.prefix() + '-legend-' + value_key;
    }

    function getLegendColorClassByKey(value_key) {
        return chart.prefix() + '-legend-color-' + value_key;
    }

    function getLegendColorSpanClass() {
        return chart.prefix() + '-legend-color-span';
    }

    function getLegendTextSpanClass() {
        return chart.prefix() + '-legend-text-span';
    }

    function getLegendSelectedClass() {
        return chart.prefix() + '-legend-selected';
    }

    function displayText(arc_data) {
        var angle = (arc_data.startAngle + arc_data.endAngle) / 2;

        var arc_element = chart.g().select('.' + getSliceClassByKey(arc_data.data.key));

        var text_element_client = arc_element.select('text').node().getBoundingClientRect();
        var text_element_width  = text_element_client.width;
        var text_element_left   = text_element_client.left;
        var text_element_right  = text_element_client.right;

        var svg_element_client = chart.svg().node().getBoundingClientRect();
        var svg_element_left  = svg_element_client.left;
        var svg_element_right = svg_element_client.right;

        var path_width  = arc_element.select('path').node().getBoundingClientRect().width;
        var path_height = arc_element.select('path').node().getBoundingClientRect().height;

        if (path_width < text_element_width || path_height < text_element_width) {
            arc_data.displayed = false;
            arc_element.select('text').classed(getSliceTextUndisplayedClass(), true);
        } else {
            arc_data.displayed = true;
        }

        if (angle > Math.PI) {
            if (text_element_left < svg_element_left && arc_data.displayed) {
                arc_data.displayed = false;
                arc_element.select('text').classed(getSliceTextUndisplayedClass(), true);
            } else if (arc_data.displayed) {
                arc_data.displayed = true;
                arc_element.select('text').classed(getSliceTextUndisplayedClass(), false);
            }
        } else {
            if (text_element_right > svg_element_right && arc_data.displayed) {
                arc_data.displayed = false;
                arc_element.select('text').classed(getSliceTextUndisplayedClass(), true);
            } else if (arc_data.displayed) {
                arc_data.displayed = true;
                arc_element.select('text').classed(getSliceTextUndisplayedClass(), false);
            }
        }
    }

    function replaceText(arc_data) {
        var arc_element = chart.g().select('.' + getSliceClassByKey(arc_data.data.key));
        var angle       = (arc_data.startAngle + arc_data.endAngle) / 2;

        var text_element_client = arc_element.select('text').node().getBoundingClientRect();
        var text_element_left   = text_element_client.left;
        var text_element_right  = text_element_client.right;

        var svg_element_client = chart.svg().node().getBoundingClientRect();
        var svg_element_left   = svg_element_client.left;
        var svg_element_right  = svg_element_client.right;

        if (angle > Math.PI) {
            if (text_element_left < svg_element_left) {
                arc_element.select('text').style('text-anchor', 'start');
            }
        } else {
            if (text_element_right > svg_element_right) {
                arc_element.select('text').style('text-anchor', 'end');
            }
        }
    }

    return chart;
};
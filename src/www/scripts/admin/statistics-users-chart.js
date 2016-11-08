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
(function() {

    function statisticsUsersPieChart() {
        statisticsUsersPieChart.init();

        function resize() {
            statisticsUsersPieChart.initSize();

            statisticsUsersPieChart.svg().attr('width', statisticsUsersPieChart.width())
                .attr('height', statisticsUsersPieChart.height());

            statisticsUsersPieChart.g()
                .attr('transform', 'translate(' + (statisticsUsersPieChart.width()  / 2) + ',' + (statisticsUsersPieChart.height() / 2) + ')');

            statisticsUsersPieChart.redraw();
        }

        window.addEventListener('resize', function() {
            resize();
        });

        return statisticsUsersPieChart;
    }

    function getGraphId() {
        return 'siteadmin-homepage-statistics-users';
    }

    function getSliceClassByKey(value_key) {
        return 'siteadmin-homepage-users-slice-' + value_key;
    }

    function getSlicePathClass() {
        return 'siteadmin-homepage-users-slice-path';
    }

    function getSliceTextClass() {
        return 'siteadmin-homepage-users-slice-text';
    }

    function getSliceTextUndisplayedClass() {
        return 'siteadmin-homepage-users-slice-text-undisplayed';
    }

    function getLegendId() {
        return 'siteadmin-homepage-users-legend';
    }

    function getLegendClass() {
        return 'siteadmin-homepage-users-legend';
    }

    function getLegendClassByKey(value_key) {
        return 'siteadmin-homepage-users-legend-' + value_key;
    }

    function getLegendColorClassByKey(value_key) {
        return 'siteadmin-homepage-users-legend-color-' + value_key;
    }

    function getLegendColorSpanClass() {
        return 'siteadmin-homepage-users-legend-color-span';
    }

    function getLegendTextSpanClass() {
        return 'siteadmin-homepage-users-legend-text-span';
    }

    function getLegendSelectedClass() {
        return 'siteadmin-homepage-users-legend-selected';
    }

    function displayText(arc_data) {
        var angle = (arc_data.startAngle + arc_data.endAngle) / 2;

        var arc_element = statisticsUsersPieChart.g().select('.' + getSliceClassByKey(arc_data.data.key));

        var text_element_client = arc_element.select('text').node().getBoundingClientRect();
        var text_element_width  = text_element_client.width;
        var text_element_left   = text_element_client.left;
        var text_element_right  = text_element_client.right;

        var svg_element_client = statisticsUsersPieChart.svg().node().getBoundingClientRect();
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
        var arc_element = statisticsUsersPieChart.g().select('.' + getSliceClassByKey(arc_data.data.key));
        var angle       = (arc_data.startAngle + arc_data.endAngle) / 2;

        var text_element_client = arc_element.select('text').node().getBoundingClientRect();
        var text_element_left   = text_element_client.left;
        var text_element_right  = text_element_client.right;

        var svg_element_client = statisticsUsersPieChart.svg().node().getBoundingClientRect();
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

    statisticsUsersPieChart.init = function () {
        statisticsUsersPieChart.divGraph(d3.select('#' + getGraphId()));

        statisticsUsersPieChart.initSize();

        statisticsUsersPieChart.svg(statisticsUsersPieChart.divGraph().append('svg')
            .attr('width', statisticsUsersPieChart.width())
            .attr('height', statisticsUsersPieChart.height()));

        statisticsUsersPieChart.g(statisticsUsersPieChart.svg().append('g')
            .attr('transform', 'translate(' + (statisticsUsersPieChart.width() / 2) + ',' + (statisticsUsersPieChart.height() / 2) + ')'));

        statisticsUsersPieChart.data(JSON.parse(statisticsUsersPieChart.divGraph().node().dataset.statisticsUsers));

        statisticsUsersPieChart.initArc();
        statisticsUsersPieChart.initPie();
        statisticsUsersPieChart.initGraph();
        statisticsUsersPieChart.initLegend();
        statisticsUsersPieChart.initText();
    };

    statisticsUsersPieChart.initSize = function () {
        var client_rect_width = statisticsUsersPieChart.divGraph().node().getBoundingClientRect().width,
            width             = client_rect_width / 2 - 50 < 110 ? 110 : client_rect_width / 2 - 50,
            height            = client_rect_width / 2 - 50 < 110 ? 110 : client_rect_width / 2 - 50,
            radius            = Math.min(width, height);

        statisticsUsersPieChart.width(width);
        statisticsUsersPieChart.height(height);
        statisticsUsersPieChart.radius(radius);
    };

    statisticsUsersPieChart.initArc = function() {
        var arc = d3.arc()
            .innerRadius(statisticsUsersPieChart.radius() / 4.5)
            .outerRadius(statisticsUsersPieChart.radius() / 3);

        var arc_text = d3.arc()
            .innerRadius(statisticsUsersPieChart.radius() / 3)
            .outerRadius(statisticsUsersPieChart.radius() / 3 + 20);

        statisticsUsersPieChart.arc(arc);
        statisticsUsersPieChart.arcText(arc_text);
    };

    statisticsUsersPieChart.initPie = function() {
        var pie = d3.pie()
            .value(function(d) { return d.users; })
            .sort(null);

        statisticsUsersPieChart.pie(pie);
    };

    statisticsUsersPieChart.initLegend = function() {
        var svg_legend = d3.select('#' + getGraphId()).append('div')
            .attr('id', getLegendId())
            .append('ul');

        var legend = svg_legend.selectAll('.' + getLegendClass())
            .data(statisticsUsersPieChart.data())
            .enter().append('li')
            .attr('class', function(d) { return getLegendClass() + ' ' + getLegendClassByKey(d.key); });

        legend.append('span')
            .attr('class', function(d) { return getLegendColorSpanClass() + ' ' + getLegendColorClassByKey(d.key); });

        legend.append('span')
            .attr('class', getLegendTextSpanClass())
            .text(function(d) { return d.label; });

        legend.on('mouseover', onOverValue)
            .on('mouseout', onOutValue);

        function onOverValue(d) {
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.key) + ' path')
                .transition()
                .attr('transform', 'scale(1.05)');
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), false);
            d3.select('.' + getLegendClassByKey(d.key)).classed(getLegendSelectedClass(), true);
            replaceText(statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.key)).datum());
        }

        function onOutValue(d) {
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.key) + ' path')
                .transition()
                .attr('transform', 'scale(1)');
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), function(d) {
                    if (! d.displayed) {
                        return true;
                    }
                });
            d3.select('.' + getLegendClassByKey(d.key)).classed(getLegendSelectedClass(), false);
        }
    };

    statisticsUsersPieChart.initGraph = function () {
        var arc_elements = statisticsUsersPieChart.g().selectAll('.arc')
            .data(statisticsUsersPieChart.pie()(statisticsUsersPieChart.data()))
            .enter().append('g')
            .attr('class', function (d) { return 'arc ' + getSliceClassByKey(d.data.key); });

        arc_elements.append('path')
            .attr('class', getSlicePathClass())
            .attr('d', statisticsUsersPieChart.arc());

        arc_elements.append('text')
            .attr('class', getSliceTextClass())
            .attr('transform', function(d) {
                return 'translate(' + statisticsUsersPieChart.arcText().centroid(d) + ')';
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
                return function(t) { return statisticsUsersPieChart.arc()(i(t)); };
            });

        function onOverValue(d) {
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.data.key) + ' path')
                .transition()
                .attr('transform', 'scale(1.05)');
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.data.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), false);
            d3.select('.' + getLegendClassByKey(d.data.key)).classed(getLegendSelectedClass(), true);
            replaceText(d);
        }

        function onOutValue(d) {
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.data.key) + ' path')
                .transition()
                .attr('transform', 'scale(1)');
            statisticsUsersPieChart.g().select('.' + getSliceClassByKey(d.data.key) + ' text')
                .classed(getSliceTextUndisplayedClass(), function(d) {
                    if (! d.displayed) {
                        return true;
                    }
                });
            d3.select('.' + getLegendClassByKey(d.data.key)).classed(getLegendSelectedClass(), false);
        }
    };

    statisticsUsersPieChart.initText = function () {
        d3.selectAll('.arc').each(function (d) {
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

    statisticsUsersPieChart.redraw = function () {
        statisticsUsersPieChart.initSize();

        statisticsUsersPieChart.svg().attr('width', statisticsUsersPieChart.width())
            .attr('height', statisticsUsersPieChart.height());

        statisticsUsersPieChart.g()
            .attr('transform', 'translate(' + (statisticsUsersPieChart.width()  / 2) + ',' + (statisticsUsersPieChart.height() / 2) + ')');

        statisticsUsersPieChart.initArc();

        statisticsUsersPieChart.g().selectAll('path').attr('d', statisticsUsersPieChart.arc());

        statisticsUsersPieChart.g().selectAll('text').attr('transform', function(d) {
            return 'translate(' + (statisticsUsersPieChart.arcText().centroid(d)) + ')';
        });

        statisticsUsersPieChart.initText();
    };

    statisticsUsersPieChart.divGraph = function (new_div_graph) {
        if (!arguments.length) {
            return div_graph;
        }
        div_graph = new_div_graph;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.svg = function (new_svg) {
        if (!arguments.length) {
            return svg;
        }
        svg = new_svg;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.g = function (new_g) {
        if (!arguments.length) {
            return g;
        }
        g = new_g;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.arc = function (new_arc) {
        if (!arguments.length) {
            return arc;
        }
        arc = new_arc;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.arcText = function (new_arc_text) {
        if (!arguments.length) {
            return arc_text;
        }
        arc_text = new_arc_text;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.pie = function (new_pie) {
        if (!arguments.length) {
            return pie;
        }
        pie = new_pie;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.data = function (new_data) {
        if (!arguments.length) {
            return data;
        }
        data = new_data;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.width = function (new_width) {
        if (!arguments.length) {
            return width;
        }
        width = new_width;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.height = function (new_height) {
        if (!arguments.length) {
            return height;
        }
        height = new_height;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.radius = function (new_radius) {
        if (!arguments.length) {
            return radius;
        }
        radius = new_radius;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart.colorScale = function (new_color_scale) {
        if (!arguments.length) {
            return color_scale;
        }
        color_scale = new_color_scale;
        return statisticsUsersPieChart;
    };

    statisticsUsersPieChart();
} ());
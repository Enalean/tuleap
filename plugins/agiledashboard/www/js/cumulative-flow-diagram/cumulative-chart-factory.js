/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
import * as d3 from 'd3';
import cumulativeFlowChart from './cumulative-chart.js';

export default function (options) {
    options = options || {};

    var self = this;

    self.build = function() {
        var chart = {};

        cumulativeFlowChart(chart);

        chart.width(options.width);
        chart.height(options.height);
        chart.margin(options.margin);
        chart.data(options.data);
        chart.legendText(options.legend_text);
        chart.localizedFormat(options.localized_format);
        chart.divGraph(d3.select('#' + options.graph_id));

        return chart;
    };

    return self;
};

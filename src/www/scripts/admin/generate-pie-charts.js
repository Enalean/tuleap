/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

import { StatisticsPieChart } from "../charts-builders/statistics-pie-chart.js";

document.addEventListener("DOMContentLoaded", () => {
    const PIE_CHART_MAX_HEIGHT = 250;
    const PIE_CHART_MARGIN = 50;
    const PIE_CHART_LEGEND_MARGIN = 10;

    initializePieCharts();

    function initializePieCharts() {
        const pie_chart_elements = document.getElementsByClassName("siteadmin-homepage-pie-chart");

        [].forEach.call(pie_chart_elements, function (pie_chart_element) {
            const pie_chart_element_sizes = getSizes(pie_chart_element);

            const pie_chart = new StatisticsPieChart({
                id: pie_chart_element.id,
                prefix: pie_chart_element.id,
                general_prefix: "siteadmin-homepage-pie-chart",
                data: JSON.parse(pie_chart_element.dataset.statistics),
                width: pie_chart_element_sizes.width,
                height: pie_chart_element_sizes.height,
                radius: pie_chart_element_sizes.radius,
            });

            pie_chart.init();

            window.addEventListener("resize", function () {
                const sizes = getSizes(pie_chart_element);
                pie_chart.redraw(sizes);
            });
        });

        initializePieChartsLegendSize();
    }

    function initializePieChartsLegendSize() {
        let legend_max_width = 0;
        const legend_li_elements = document.querySelectorAll(
            ".siteadmin-homepage-pie-chart-legend > li"
        );

        [].forEach.call(legend_li_elements, function (li_element) {
            const li_width = li_element.getBoundingClientRect().width;

            if (li_width > legend_max_width) {
                legend_max_width = li_width;
            }
        });

        [].forEach.call(legend_li_elements, function (legend_li_element) {
            legend_li_element.style.width = legend_max_width + PIE_CHART_LEGEND_MARGIN + "px";
        });
    }

    function getSizes(element) {
        const client_rect_width = element.getBoundingClientRect().width,
            width = client_rect_width / 2,
            height =
                PIE_CHART_MAX_HEIGHT > client_rect_width / 2
                    ? client_rect_width / 2
                    : PIE_CHART_MAX_HEIGHT,
            radius = Math.min(width - PIE_CHART_MARGIN, height - PIE_CHART_MARGIN);

        return { width: width, height: height, radius: radius };
    }
});

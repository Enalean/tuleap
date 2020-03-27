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
import { StatisticsPieChart } from "charts-builders/statistics-pie-chart.js";

document.addEventListener("DOMContentLoaded", () => {
    const PIE_CHART_MAX_HEIGHT = 250;
    const PIE_CHART_MARGIN = 50;
    const PIE_CHART_LEGEND_MARGIN = 10;

    initializePieChart();

    function initializePieChart() {
        const pie_chart_mount_point = document.getElementById("disk-usage-pie");
        const pie_chart_element_sizes = getSizes(pie_chart_mount_point);

        const pie_chart = new StatisticsPieChart({
            id: pie_chart_mount_point.id,
            prefix: pie_chart_mount_point.id,
            general_prefix: "statistics-pie-chart",
            data: JSON.parse(pie_chart_mount_point.dataset.diskUsage),
            width: pie_chart_element_sizes.width,
            height: pie_chart_element_sizes.height,
            radius: pie_chart_element_sizes.radius,
        });

        pie_chart.init();

        window.addEventListener("resize", () => {
            const sizes = getSizes(pie_chart_mount_point);
            pie_chart.redraw(sizes);
        });

        initializePieChartLegendSize();
    }

    function initializePieChartLegendSize() {
        let legend_max_width = 0;
        const legend_li_elements = document.querySelectorAll("#disk-usage-pie > li");

        for (const li_element of legend_li_elements) {
            const li_width = li_element.getBoundingClientRect().width;

            if (li_width > legend_max_width) {
                legend_max_width = li_width;
            }
        }

        for (const legend_li_element of legend_li_elements) {
            legend_li_element.style.width = legend_max_width + PIE_CHART_LEGEND_MARGIN + "px";
        }
    }

    function getSizes() {
        const svg_width = 600;
        const width = svg_width / 2;

        const height = PIE_CHART_MAX_HEIGHT > width ? width : PIE_CHART_MAX_HEIGHT;

        const radius = Math.min(width - PIE_CHART_MARGIN, height - PIE_CHART_MARGIN);

        return { width: width, height: height, radius: radius };
    }
});

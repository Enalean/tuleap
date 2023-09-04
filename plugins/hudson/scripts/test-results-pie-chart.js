/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import { StatisticsPieChart } from "@tuleap/pie-chart";

document.addEventListener("DOMContentLoaded", () => {
    const PIE_CHART_MAX_HEIGHT = 250;
    const PIE_CHART_MARGIN = 50;
    const PIE_CHART_LEGEND_MARGIN = 10;

    /*
     * Given that the mount point is not in the DOM yet because the backend has to query the Jenkins server first,
     * we need to observe the mutations of the widget content div. When its children are modified, then it means
     * that the mount_point has been inserted so we can init the graph safely.
     */
    const widget_classname = window.location.href.includes("my")
        ? "plugin_hudson_my_jobtestresults"
        : "plugin_hudson_project_jobtestresults";

    const widgets = document.querySelectorAll(`.dashboard-widget-content-${widget_classname}`);

    const config = { childList: true, subtree: true };

    for (const widget of widgets) {
        const observer = new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.type === "childList") {
                    observer.disconnect();
                    initializePieChart(mutation.target);
                }
            }
        });

        observer.observe(widget, config);
    }

    function initializePieChart(widget) {
        const pie_chart_mount_point = widget.querySelector(".test-results-pie");
        if (!pie_chart_mount_point) {
            return;
        }

        const pie_chart_element_sizes = getSizes(pie_chart_mount_point);

        const pie_chart = new StatisticsPieChart({
            id: pie_chart_mount_point.id,
            prefix: "test-results-pie",
            general_prefix: "test-results-pie-chart",
            data: JSON.parse(pie_chart_mount_point.dataset.testResults),
            width: pie_chart_element_sizes.width,
            height: pie_chart_element_sizes.height,
            radius: pie_chart_element_sizes.radius,
        });

        pie_chart.init();

        window.addEventListener("resize", () => {
            const sizes = getSizes(pie_chart_mount_point);
            pie_chart.redraw(sizes);
        });

        initializePieChartLegendSize(pie_chart_mount_point);
    }

    function initializePieChartLegendSize(pie_chart_mount_point) {
        let legend_max_width = 0;
        const legend_li_elements = pie_chart_mount_point.querySelectorAll(
            ".test-results-pie-legend > li",
        );

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

    function getSizes(element) {
        const client_rect_width = element.getBoundingClientRect().width;
        const width = client_rect_width / 2;
        const height = PIE_CHART_MAX_HEIGHT > width ? client_rect_width / 2 : PIE_CHART_MAX_HEIGHT;

        const radius = Math.min(width - PIE_CHART_MARGIN, height - PIE_CHART_MARGIN);

        return { width: width, height: height, radius: radius };
    }
});

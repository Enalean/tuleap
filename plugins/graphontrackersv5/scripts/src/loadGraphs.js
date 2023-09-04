/*
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

import { sprintf } from "sprintf-js";
import { getChartData } from "./rest-querier.js";
import { gettext_provider } from "./gettext_provider.js";
import { bar } from "./graphs-bar.js";
import { groupedbar } from "./graphs-groupedbar.js";
import { graphOnTrackerPie } from "./graphs-pie.js";
import { cumulativeflow } from "./graph-cumulative-flow.js";

document.addEventListener("DOMContentLoaded", () => {
    const locale = document.body.dataset.userLocale;
    gettext_provider.setLocale(locale);

    const graphs = document.querySelectorAll(".plugin_graphontrackersv5_chart");
    for (const graph of graphs) {
        buildGraph(graph);
    }
});

const TYPE_BAR = "bar";
const TYPE_GROUPED_BAR = "groupedbar";
const TYPE_PIE = "pie";
const TYPE_CUMULATIVE_FLOW_CHART = "cumulativeflow";

async function buildGraph(graph_node) {
    const { graphId, rendererId, reportId, doesNotUseD3, inDashboard } = graph_node.dataset;

    if (doesNotUseD3 === "true") {
        return;
    }

    const spinner_node = showSpinner(graph_node);
    try {
        const graph_data = await getChartData(reportId, rendererId, graphId, inDashboard);
        graphFactory(graphId, graph_data);
    } catch (e) {
        if (!Object.prototype.hasOwnProperty.call(e, "response")) {
            showError(graph_node, e.message);
            throw e;
        }

        try {
            const json_error = await e.response.json();

            if (Object.prototype.hasOwnProperty.call(json_error, "error_message")) {
                showError(graph_node, json_error.error_message);
                return;
            }
        } catch (e) {
            showError(graph_node, e.message);
            throw e;
        }

        showError(graph_node, e.message);
        throw e;
    } finally {
        graph_node.removeChild(spinner_node);
    }
}

function graphFactory(graph_id, graph_data) {
    const { type } = graph_data;

    if (type === TYPE_BAR) {
        bar(graph_id, graph_data);
        return;
    }
    if (type === TYPE_GROUPED_BAR) {
        groupedbar(graph_id, graph_data);
        return;
    }
    if (type === TYPE_PIE) {
        graphOnTrackerPie(graph_id, graph_data);
        return;
    }
    if (type === TYPE_CUMULATIVE_FLOW_CHART) {
        cumulativeflow(graph_id, graph_data);
    }
}

function showError(graph_node, error) {
    const error_message = sprintf(
        gettext_provider.gettext("An error occurred while loading the chart: %s"),
        error,
    );
    const error_node = document.createElement("div");
    error_node.classList.add(
        "alert",
        "alert-error",
        "tlp-alert-danger",
        "graphontrackersv5-chart-error",
    );
    error_node.insertAdjacentText("beforeend", error_message);
    graph_node.appendChild(error_node);
}

function showSpinner(graph_node) {
    const spinner_node = document.createElement("div");
    spinner_node.classList.add("graphontrackersv5-chart-spinner");
    graph_node.appendChild(spinner_node);
    return spinner_node;
}

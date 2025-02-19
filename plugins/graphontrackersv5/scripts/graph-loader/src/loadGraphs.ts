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

import "../themes/style.scss";
import { getAttributeOrThrow } from "@tuleap/dom";
import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";
import { sprintf } from "sprintf-js";
import { bar } from "./graphs-bar.js";
import { groupedbar } from "./graphs-groupedbar.js";
import { graphOnTrackerPie } from "./graphs-pie.js";
import { cumulativeflow } from "./graph-cumulative-flow.js";
import { getChartData } from "./rest-querier";
import type { GraphData } from "./types";
import { TYPE_BAR, TYPE_CUMULATIVE_FLOW, TYPE_GROUPED_BAR, TYPE_PIE } from "./types";

document.addEventListener("DOMContentLoaded", async () => {
    const gettext_provider = await initGettext(
        getLocaleWithDefault(document),
        "tuleap-graphontrackersv5",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    function buildGraph(graph_node: HTMLElement): void {
        const graph_id = getAttributeOrThrow(graph_node, "data-graph-id");
        const renderer_id = getAttributeOrThrow(graph_node, "data-renderer-id");
        const report_id = getAttributeOrThrow(graph_node, "data-report-id");
        const in_dashboard = getAttributeOrThrow(graph_node, "data-in-dashboard");

        const spinner_node = showSpinner(graph_node);
        getChartData(
            Number.parseInt(report_id, 10),
            Number.parseInt(renderer_id, 10),
            Number.parseInt(graph_id, 10),
            in_dashboard,
        )
            .match(
                (graph_data) => graphFactory(Number.parseInt(graph_id, 10), graph_data),
                (fault) => showError(graph_node, fault.toString()),
            )
            .then(() => graph_node.removeChild(spinner_node));
    }

    function graphFactory(graph_id: number, graph_data: GraphData): void {
        if (graph_data.type === TYPE_BAR) {
            bar(graph_id, graph_data);
        } else if (graph_data.type === TYPE_GROUPED_BAR) {
            groupedbar(graph_id, graph_data);
        } else if (graph_data.type === TYPE_PIE) {
            graphOnTrackerPie(graph_id, graph_data);
        } else if (graph_data.type === TYPE_CUMULATIVE_FLOW) {
            cumulativeflow(graph_id, graph_data);
        }
    }

    function showError(graph_node: HTMLElement, error: string): void {
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

    function showSpinner(graph_node: HTMLElement): HTMLDivElement {
        const spinner_node = document.createElement("div");
        spinner_node.classList.add("graphontrackersv5-chart-spinner");
        graph_node.appendChild(spinner_node);
        return spinner_node;
    }

    document.querySelectorAll<HTMLElement>(".plugin_graphontrackersv5_chart").forEach(buildGraph);
});

/**
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

import "../themes/burnup-chart.scss";
import moment from "moment";
import "moment/dist/locale/fr";
import { createBurnupChart } from "./burnup-chart-drawer.js";
import { transformToGenericBurnupData } from "@tuleap/plugin-agiledashboard-burnup-data-transformer";
import { buildProvider } from "./gettext-provider.js";

document.addEventListener("DOMContentLoaded", () => {
    const chart_container = document.getElementById("burnup-chart");

    if (!chart_container) {
        return;
    }

    const burnup_data = JSON.parse(chart_container.dataset.burnup);
    const container_width = chart_container.clientWidth;
    const locale = chart_container.dataset.locale;
    const mode = chart_container.dataset.mode;

    const gettext_provider = buildProvider(locale);
    moment.locale(locale);

    let left_progression_label = gettext_provider.gettext("%s - Team effort");
    if (mode === "count") {
        left_progression_label = gettext_provider.gettext("%s - Closed elements");
    }

    let progression_label = gettext_provider.gettext("Team effort");
    if (mode === "count") {
        progression_label = gettext_provider.gettext("Closed");
    }

    let total_label = gettext_provider.gettext("Total effort");
    if (mode === "count") {
        total_label = gettext_provider.gettext("Total");
    }

    const chart_props = {
        graph_width: container_width,
        graph_height: container_width / 1.33,
        tooltip_date_format: gettext_provider.gettext("MM/DD"),
        left_legend_title: left_progression_label,
        left_legend_date_format: gettext_provider.gettext("ddd DD"),
        legend_badge_default: gettext_provider.gettext("n/k"),
        margins: {
            top: 50,
            right: 80,
            bottom: 60,
            left: 80,
        },
    };

    const chart_legends = [
        {
            label: gettext_provider.gettext("Ideal burnup"),
            classname: "chart-plot-ideal-burnup",
        },
        {
            label: progression_label,
            classname: "chart-plot-team-effort",
        },
        {
            label: total_label,
            classname: "chart-plot-total-effort",
        },
    ];

    const generic_burnup_data = transformToGenericBurnupData(burnup_data, mode);
    createBurnupChart(
        { chart_container, chart_props, chart_legends, generic_burnup_data, mode },
        gettext_provider,
    );
});

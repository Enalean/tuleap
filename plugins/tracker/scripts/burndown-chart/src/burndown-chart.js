/**
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

import moment from "moment";
import { createBurndownChart } from "./burndown-chart-drawer.js";
import { gettext_provider } from "./gettext-provider.js";

document.addEventListener("DOMContentLoaded", () => {
    const chart_container = document.getElementById("burndown-chart");

    if (!chart_container) {
        return;
    }

    const burndown_data = JSON.parse(chart_container.dataset.burndown);
    const container_width = chart_container.clientWidth;
    const locale = chart_container.dataset.locale;

    gettext_provider.setLocale(locale);
    moment.locale(locale);

    const chart_props = {
        graph_width: container_width,
        graph_height: container_width / 1.33,
        tooltip_date_format: gettext_provider.gettext("MM/DD"),
        left_legend_title: gettext_provider.gettext("%s - Remaining effort"),
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
            label: gettext_provider.gettext("Ideal burndown"),
            classname: "chart-plot-ideal-burndown",
        },
        {
            label: gettext_provider.gettext("Remaining effort"),
            classname: "chart-plot-remaining-effort",
        },
    ];

    createBurndownChart({ chart_container, chart_props, chart_legends, burndown_data });
});

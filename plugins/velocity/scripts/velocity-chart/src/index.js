/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { gettext_provider } from "./gettext-provider.js";
import { VelocityChartDrawer } from "./velocity-chart-drawer.js";

document.addEventListener("DOMContentLoaded", () => {
    const mount_point = document.getElementById("velocity-chart");

    if (!mount_point) {
        return;
    }

    const locale = document.body.dataset.userLocale;
    const sprints_data = JSON.parse(mount_point.dataset.sprints);
    const container_width = mount_point.clientWidth;

    gettext_provider.setLocale(locale);

    const chart_props = {
        graph_width: container_width,
        graph_height: container_width / 1.33,
        bands_paddings: 0.5,
        default_max_velocity: 5,
        minimum_bar_height: 2, // To have a tiny bar when velocity worth 0
        tooltip_date_format: gettext_provider.gettext("MM/DD"),
        abcissa_labels_margin: 20,
        margins: {
            top: 50,
            right: 80,
            bottom: 100,
            left: 80,
        },
    };

    const chart = new VelocityChartDrawer({
        mount_point,
        chart_props,
        sprints_data,
    });

    chart.draw();
});

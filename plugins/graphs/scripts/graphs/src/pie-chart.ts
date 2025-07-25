/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { StatisticsPieChartData } from "./type";
import { initGettext, getPOFileFromLocaleWithoutExtension } from "@tuleap/gettext";

async function emptyState(container: HTMLElement, language: string): Promise<void> {
    const gettext_provider = await initGettext(
        language,
        "graphs",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );
    const msg = document.createElement("div");
    msg.className = "empty-state";
    msg.textContent = gettext_provider.gettext("No data available");
    container.appendChild(msg);
}

export function createPieChartElement(pie_data: StatisticsPieChartData): HTMLElement {
    const existing_container = document.querySelector(".pie-chart-container");
    if (existing_container && existing_container.parentElement) {
        existing_container.parentElement.removeChild(existing_container);
    }
    const container = document.createElement("div");
    container.className = "pie-chart-container";
    const chart_id = "normal-pie";
    container.id = chart_id;
    if (!pie_data.data || pie_data.data.length === 0) {
        emptyState(container, pie_data.language);
        return container;
    }
    document.body.appendChild(container);
    container.innerHTML = "";
    const chart = new StatisticsPieChart({
        id: chart_id,
        data: pie_data.data,
        width: pie_data.width,
        height: pie_data.height,
        radius: Math.min(pie_data.width, pie_data.height) / 2.5,
        prefix: pie_data.prefix,
        general_prefix: pie_data.general_prefix,
    });
    chart.init();
    return container;
}

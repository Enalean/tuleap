/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { ChartPropsWithRadius, DataPieChart } from "@tuleap/pie-chart";
import { select } from "d3-selection";
import { StatisticsPieChart } from "@tuleap/pie-chart";

const MIN_ARC_TO_DISPLAY = Math.PI / (10 * Math.PI) / 2;

export function createPieChart(
    chart_container: HTMLElement,
    pie_chart_element_sizes: ChartPropsWithRadius,
    data_stat_campaign: DataPieChart[],
): void {
    const data_to_display = getDataToDisplay(data_stat_campaign);

    const stat = new StatisticsPieChart({
        id: chart_container.id,
        data: data_to_display,
        general_prefix: "release-widget-pie-chart-ttm",
        height: pie_chart_element_sizes.height,
        width: pie_chart_element_sizes.width,
        radius: pie_chart_element_sizes.radius,
        prefix: "release-widget-pie-chart-ttm",
    });

    stat.init();

    replaceValue(chart_container, data_stat_campaign);
}

export function getDataToDisplay(data_stat_campaign: DataPieChart[]): DataPieChart[] {
    const data_to_display: DataPieChart[] = [];

    data_stat_campaign.forEach((data) => {
        const min_value_to_display = getSumOfValue(data_stat_campaign) * MIN_ARC_TO_DISPLAY;

        data_to_display.push({
            ...data,
            count:
                data.count > 0 && data.count <= Math.floor(min_value_to_display)
                    ? Math.floor(min_value_to_display)
                    : data.count,
        });
    });

    return data_to_display;
}

export function getSumOfValue(data_stat_campaign: DataPieChart[]): number {
    let sum = 0;
    data_stat_campaign.forEach((data) => {
        sum += data.count;
    });

    return sum;
}

export function replaceValue(
    chart_container: HTMLElement,
    data_stat_campaign: DataPieChart[],
): void {
    data_stat_campaign.forEach((data) => {
        select(chart_container)
            .select(".release-widget-pie-chart-ttm-slice-" + data.key)
            .select("text")
            .text(data.count > 0 ? data.count : "");
    });
}

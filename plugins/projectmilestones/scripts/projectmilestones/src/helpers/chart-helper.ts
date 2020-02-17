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

import { ArtifactMilestone, BurndownData, BurnupData } from "../type";
export function getBurndownDataFromType(chart_data: ArtifactMilestone): null | BurndownData {
    const iterator_milestone_chart = chart_data.values.values();

    for (const chart of iterator_milestone_chart) {
        if (chart.type === "burndown") {
            const burndown_data = chart.value;
            return { ...burndown_data, label: chart.label };
        }
    }

    return null;
}

export function getBurnupDataFromType(chart_data: ArtifactMilestone): null | BurnupData {
    const iterator_milestone_chart = chart_data.values.values();

    for (const chart of iterator_milestone_chart) {
        if (chart.type === "burnup") {
            const burnup_data = chart.value;
            return { ...burnup_data, label: chart.label };
        }
    }

    return null;
}

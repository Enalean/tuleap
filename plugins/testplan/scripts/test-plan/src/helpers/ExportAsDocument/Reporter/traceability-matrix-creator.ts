/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import type { Campaign, DateTimeLocaleInformation, TraceabilityMatrixElement } from "../../../type";
import { getExecutions } from "./execution-querier";

export async function getTraceabilityMatrix(
    campaigns: ReadonlyArray<Campaign>,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<TraceabilityMatrixElement[]> {
    const matrix_map: Map<number, TraceabilityMatrixElement> = new Map();

    for (const campaign of campaigns) {
        const executions = await getExecutions(campaign);
        for (const execution of executions) {
            if (execution.definition.requirement === null) {
                continue;
            }

            let submitted_on: string | null = null;
            if (execution.previous_result !== null) {
                const submitted_on_date = new Date(execution.previous_result.submitted_on);
                const { locale, timezone } = datetime_locale_information;
                submitted_on =
                    submitted_on_date.toLocaleDateString(locale, {
                        timeZone: timezone,
                    }) +
                    " " +
                    submitted_on_date.toLocaleTimeString(locale, { timeZone: timezone });
            }

            const requirement = {
                id: execution.definition.requirement.id,
                title:
                    execution.definition.requirement.title ?? execution.definition.requirement.xref,
            };
            matrix_map.set(requirement.id, {
                requirement,
                tests: [
                    ...(matrix_map.get(requirement.id)?.tests ?? []),
                    {
                        id: execution.definition.id,
                        title: execution.definition.summary,
                        campaign: campaign.label,
                        status: execution.previous_result?.status ?? null,
                        executed_by: execution.previous_result?.submitted_by.display_name ?? null,
                        executed_on: submitted_on,
                    },
                ],
            });
        }
    }

    return [...matrix_map.values()];
}

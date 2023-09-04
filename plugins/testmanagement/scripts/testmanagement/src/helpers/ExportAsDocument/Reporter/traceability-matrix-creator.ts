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
import type {
    DateTimeLocaleInformation,
    ExecutionsForCampaignMap,
    TraceabilityMatrixElement,
    TraceabilityMatrixTest,
    TraceabilityMatrixRequirement,
} from "../../../type";

export function getTraceabilityMatrix(
    executions_map: ExecutionsForCampaignMap,
    datetime_locale_information: DateTimeLocaleInformation,
): TraceabilityMatrixElement[] {
    const matrix_map: Map<number, TraceabilityMatrixElement> = new Map();

    for (const { campaign, executions } of executions_map.values()) {
        for (const execution of executions) {
            if (execution.definition.all_requirements.length === 0) {
                continue;
            }

            let executed_on: string | null = null;
            let executed_on_date: Date | null = null;
            if (execution.previous_result !== null) {
                executed_on_date = new Date(execution.previous_result.submitted_on);
                const { locale, timezone } = datetime_locale_information;
                executed_on =
                    executed_on_date.toLocaleDateString(locale, {
                        timeZone: timezone,
                    }) +
                    " " +
                    executed_on_date.toLocaleTimeString(locale, { timeZone: timezone });
            }

            for (const { id, title, xref, tracker } of execution.definition.all_requirements) {
                const requirement: TraceabilityMatrixRequirement = {
                    id,
                    title: title ?? xref,
                    tracker_id: tracker.id,
                };

                const already_encountered_requirement = matrix_map.get(requirement.id);
                const tests: Map<number, TraceabilityMatrixTest> =
                    already_encountered_requirement?.tests ?? new Map();
                const already_encountered_test = tests.get(execution.definition.id);

                if (already_encountered_test) {
                    if (!executed_on_date) {
                        continue;
                    }

                    if (
                        already_encountered_test.executed_on_date &&
                        executed_on_date <= already_encountered_test.executed_on_date
                    ) {
                        continue;
                    }
                }
                tests.set(execution.definition.id, {
                    id: execution.definition.id,
                    title: execution.definition.summary,
                    campaign: campaign.label,
                    status: execution.previous_result?.status ?? null,
                    executed_by: execution.previous_result?.submitted_by.display_name ?? null,
                    executed_on,
                    executed_on_date,
                });
                matrix_map.set(requirement.id, {
                    requirement,
                    tests,
                });
            }
        }
    }

    return [...matrix_map.values()];
}

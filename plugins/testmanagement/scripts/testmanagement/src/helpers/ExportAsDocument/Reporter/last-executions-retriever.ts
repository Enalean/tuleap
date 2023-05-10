/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { ExecutionsForCampaignMap } from "../../../type";
import type { TestExecutionResponse } from "@tuleap/plugin-docgen-docx";

export function getLastExecutionForTest(
    artifact_id: number,
    executions_map: ExecutionsForCampaignMap
): TestExecutionResponse | null {
    let execution_for_test: TestExecutionResponse | null = null;
    const all_execution_for_test: TestExecutionResponse[] = [];

    for (const { executions } of executions_map.values()) {
        for (const exec of executions) {
            if (exec.definition.id === artifact_id) {
                all_execution_for_test.push(exec);
            }
        }
    }

    if (all_execution_for_test.length === 0) {
        return null;
    }

    if (all_execution_for_test.length === 1) {
        return all_execution_for_test[0];
    }

    let higher_found_execution_date: Date | null = null;
    for (const execution of all_execution_for_test) {
        if (execution.previous_result === null) {
            if (execution_for_test === null) {
                execution_for_test = execution;
            }
        } else {
            const current_execution_date = new Date(execution.previous_result.submitted_on);
            if (
                higher_found_execution_date === null ||
                current_execution_date > higher_found_execution_date
            ) {
                execution_for_test = execution;
                higher_found_execution_date = current_execution_date;
            }
        }
    }

    return execution_for_test;
}

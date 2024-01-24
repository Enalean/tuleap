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

import {
    BLOCKED_STATUS,
    FAILED_STATUS,
    NOT_RUN_STATUS,
    PASSED_STATUS,
} from "../../execution-constants.js";

function updateStatusWithStepResults(execution, ExecutionService) {
    const previous_status = execution.status;
    const new_status = computeTestStatusFromStepStatus(
        execution.definition.steps,
        Object.values(execution.steps_results),
    );
    if (ExecutionService) {
        ExecutionService.campaign["nb_of_" + previous_status]--;
        ExecutionService.campaign["nb_of_" + new_status]++;
    }

    execution.status = new_status;
}

function updateStepResults(execution, step_id, status) {
    if (typeof execution.steps_results[step_id] === "undefined") {
        execution.steps_results[step_id] = {};
    }
    Object.assign(execution.steps_results[step_id], { step_id, status });
}

function computeTestStatusFromStepStatus(step_definitions, steps_results) {
    const counts = countStepStatus(steps_results);
    if (counts[FAILED_STATUS] > 0) {
        return FAILED_STATUS;
    }
    if (counts[BLOCKED_STATUS] > 0) {
        return BLOCKED_STATUS;
    }
    if (counts[NOT_RUN_STATUS] > 0) {
        return NOT_RUN_STATUS;
    }

    return counts[PASSED_STATUS] === step_definitions.length ? PASSED_STATUS : NOT_RUN_STATUS;
}

function countStepStatus(steps_results) {
    return steps_results.reduce((count, step_result) => {
        count[step_result.status] = ++count[step_result.status] || 1;
        return count;
    }, {});
}

export { updateStatusWithStepResults, updateStepResults };

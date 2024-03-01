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

import { updateStepResults } from "./execution-with-steps-updater.js";
import { PASSED_STATUS, FAILED_STATUS, NOT_RUN_STATUS } from "../../execution-constants.js";

describe("ExecutionWithStepsUpdater", () => {
    describe("updateStepResults()", () => {
        it("Given an execution without steps_results, a step id and a status, then it will build a correct steps_results object in the execution", () => {
            const execution = {
                steps_results: {},
            };

            updateStepResults(execution, 16, PASSED_STATUS);

            expect(execution.steps_results[16]).toEqual({
                step_id: 16,
                status: PASSED_STATUS,
            });
        });

        it("Given an execution with non-empty steps_results, then it will update the steps_results status", () => {
            const execution = {
                steps_results: {
                    24: {
                        step_id: 24,
                        status: NOT_RUN_STATUS,
                    },
                },
            };

            updateStepResults(execution, 24, FAILED_STATUS);

            expect(execution.steps_results[24]).toEqual({
                step_id: 24,
                status: FAILED_STATUS,
            });
        });
    });
});

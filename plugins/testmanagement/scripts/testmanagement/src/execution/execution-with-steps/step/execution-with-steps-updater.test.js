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

import { updateStatusWithStepResults, updateStepResults } from "./execution-with-steps-updater.js";
import {
    PASSED_STATUS,
    FAILED_STATUS,
    BLOCKED_STATUS,
    NOT_RUN_STATUS,
} from "../../execution-constants.js";

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

    describe("updateStatusWithStepResults()", () => {
        it("Given one step is failed, then the test will be failed", () => {
            const execution = {
                definition: {
                    steps: [{ step_id: 68 }, { step_id: 72 }],
                },
                steps_results: {
                    68: {
                        step_id: 68,
                        status: PASSED_STATUS,
                    },
                    72: {
                        step_id: 72,
                        status: FAILED_STATUS,
                    },
                },
            };

            updateStatusWithStepResults(execution);
            expect(execution.status).toBe(FAILED_STATUS);
        });

        it("Given one step is blocked, then the test will be blocked", () => {
            const execution = {
                definition: {
                    steps: [{ step_id: 57 }, { step_id: 18 }],
                },
                steps_results: {
                    57: {
                        step_id: 57,
                        status: NOT_RUN_STATUS,
                    },
                    18: {
                        step_id: 18,
                        status: BLOCKED_STATUS,
                    },
                },
            };

            updateStatusWithStepResults(execution);
            expect(execution.status).toBe(BLOCKED_STATUS);
        });

        it("Given one step is not run, then the test will be not run", () => {
            const execution = {
                definition: {
                    steps: [{ step_id: 50 }, { step_id: 27 }],
                },
                steps_results: {
                    50: {
                        step_id: 50,
                        status: NOT_RUN_STATUS,
                    },
                    27: {
                        step_id: 27,
                        status: PASSED_STATUS,
                    },
                },
            };

            updateStatusWithStepResults(execution);
            expect(execution.status).toBe(NOT_RUN_STATUS);
        });

        it("Given all steps are passed and their number matches the number of step definitions, then the test will be passed", () => {
            const execution = {
                definition: {
                    steps: [{ step_id: 16 }, { step_id: 48 }],
                },
                steps_results: {
                    16: {
                        step_id: 16,
                        status: PASSED_STATUS,
                    },
                    48: {
                        step_id: 48,
                        status: PASSED_STATUS,
                    },
                },
            };

            updateStatusWithStepResults(execution);
            expect(execution.status).toBe(PASSED_STATUS);
        });
    });
});

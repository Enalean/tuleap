/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import execution_module from "../../execution.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./step-controller.js";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import * as feedback_state from "../../../feedback-state.js";
import * as execution_with_steps_updater from "./execution-with-steps-updater.js";

describe("StepController", () => {
    let $q,
        $rootScope,
        StepController,
        ExecutionRestService,
        setError,
        resetError,
        fake_dropdown_object;

    const $element = angular.element("<div></div>");

    beforeEach(() => {
        fake_dropdown_object = {
            hide: jest.fn(),
            show: jest.fn(),
        };

        jest.spyOn(tlp_dropdown, "createDropdown").mockReturnValue(fake_dropdown_object);

        setError = jest.spyOn(feedback_state, "setError");
        resetError = jest.spyOn(feedback_state, "resetError");

        jest.spyOn(execution_with_steps_updater, "updateStatusWithStepResults").mockImplementation(
            () => {},
        );

        angular.mock.module(execution_module);

        let $controller;
        angular.mock.inject(function (_$q_, _$rootScope_, _$controller_, _ExecutionRestService_) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            $controller = _$controller_;
            ExecutionRestService = _ExecutionRestService_;
        });

        StepController = $controller(BaseController, {
            $element,
            ExecutionRestService,
        });
    });

    describe("openDropdown()", () => {
        it("Then the dropdown will be shown", () => {
            StepController.dropdown = fake_dropdown_object;

            StepController.openDropdown();

            expect(fake_dropdown_object.show).toHaveBeenCalled();
        });
    });

    describe("Status updates", () => {
        let execution;

        beforeEach(() => {
            execution = { id: 79, steps_results: {} };
            jest.spyOn(ExecutionRestService, "updateStepStatus").mockReturnValue($q.when());
            StepController.dropdown = fake_dropdown_object;
            StepController.execution = execution;
        });

        describe("setToPassed() -", () => {
            it("Then the saving icon will be displayed and the step's status will become 'passed'", () => {
                const step_id = 93;
                StepController.step = { id: step_id };
                StepController.step_result = { status: "notrun" };

                StepController.setToPassed();
                expect(StepController.saving).toBe(true);
                $rootScope.$apply();

                expect(ExecutionRestService.updateStepStatus).toHaveBeenCalledWith(
                    execution,
                    step_id,
                    "passed",
                );
                expect(StepController.isPassed()).toBe(true);
                expect(StepController.saving).toBe(false);
                expect(StepController.dropdown.hide).toHaveBeenCalled();
                expect(resetError).toHaveBeenCalled();
            });

            it("Given a previous status update is still saving, then it will return", () => {
                StepController.saving = true;
                StepController.step = { id: 75 };
                StepController.step_result = { status: "notrun" };

                StepController.setToPassed();
                expect(ExecutionRestService.updateStepStatus).not.toHaveBeenCalled();
            });

            it("Given there is a REST error, then an error message will be displayed", () => {
                StepController.step = { id: 67 };
                StepController.step_result = { status: "failed" };
                ExecutionRestService.updateStepStatus.mockReturnValue(
                    $q.reject("This user cannot update the execution"),
                );

                StepController.setToPassed();
                $rootScope.$apply();

                expect(setError).toHaveBeenCalled();
                expect(StepController.saving).toBe(false);
            });
        });

        describe("setToFailed() -", () => {
            it("Then the step's status will become 'failed'", () => {
                const step_id = 71;
                StepController.step = { id: step_id };
                StepController.step_result = { status: "notrun" };

                StepController.setToFailed();
                expect(StepController.saving).toBe(true);
                $rootScope.$apply();

                expect(ExecutionRestService.updateStepStatus).toHaveBeenCalledWith(
                    execution,
                    step_id,
                    "failed",
                );
                expect(StepController.isFailed()).toBe(true);
                expect(StepController.saving).toBe(false);
                expect(StepController.dropdown.hide).toHaveBeenCalled();
            });
        });

        describe("setToBlocked() -", () => {
            it("Then the step's status will become 'blocked'", () => {
                const step_id = 55;
                StepController.step = { id: step_id };
                StepController.step_result = { status: "notrun" };

                StepController.setToBlocked();
                expect(StepController.saving).toBe(true);
                $rootScope.$apply();

                expect(ExecutionRestService.updateStepStatus).toHaveBeenCalledWith(
                    execution,
                    step_id,
                    "blocked",
                );
                expect(StepController.isBlocked()).toBe(true);
                expect(StepController.saving).toBe(false);
                expect(StepController.dropdown.hide).toHaveBeenCalled();
            });
        });

        describe("setToNotRun() -", () => {
            it("Then the step's status will become 'notrun'", () => {
                const step_id = 91;
                StepController.step = { id: step_id };
                StepController.step_result = { status: "passed" };

                StepController.setToNotRun();
                expect(StepController.saving).toBe(true);
                $rootScope.$apply();

                expect(ExecutionRestService.updateStepStatus).toHaveBeenCalledWith(
                    execution,
                    step_id,
                    "notrun",
                );
                expect(StepController.isNotRun()).toBe(true);
                expect(StepController.saving).toBe(false);
                expect(StepController.dropdown.hide).toHaveBeenCalled();
            });
        });
    });

    describe("sanitizedContentWithEnhancedCodeBlocks() -", () => {
        it("should allow tlp-mermaid-block but still sanitizes the html", () => {
            const trusted_as_html = StepController.sanitizedContentWithEnhancedCodeBlocks(
                "<tlp-mermaid-diagram>23<a href='\u2028javascript:alert(1)'>I am a dolphin too!</a></tlp-mermaid-diagram>",
            );

            expect(trusted_as_html.$$unwrapTrustedValue()).toBe(
                "<tlp-mermaid-diagram>23<a>I am a dolphin too!</a></tlp-mermaid-diagram>",
            );
        });
    });
});

import execution_module from "../../execution.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./step-controller.js";
import * as tlp from "tlp";
import {
    rewire$setError,
    rewire$resetError,
    restore as restoreFeedback
} from "../../../feedback-state.js";
import {
    rewire$updateStatusWithStepResults,
    restore as restoreUpdater
} from "./execution-with-steps-updater.js";

describe("StepController", () => {
    let $q,
        $rootScope,
        StepController,
        ExecutionRestService,
        setError,
        resetError,
        updateStatusWithStepResults;

    const $element = angular.element("<div></div>");
    const fake_dropdown_object = jasmine.createSpyObj("dropdown", ["hide", "show"]);
    const mockDropdown = jasmine.createSpy("dropdown").and.returnValue(fake_dropdown_object);

    beforeEach(() => {
        tlp.dropdown = mockDropdown;

        setError = jasmine.createSpy("setError");
        rewire$setError(setError);
        resetError = jasmine.createSpy("resetError");
        rewire$resetError(resetError);

        updateStatusWithStepResults = jasmine.createSpy("updateStatusWithStepResults");
        rewire$updateStatusWithStepResults(updateStatusWithStepResults);

        angular.mock.module(execution_module);

        let $controller;
        angular.mock.inject(function(_$q_, _$rootScope_, _$controller_, _ExecutionRestService_) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            $controller = _$controller_;
            ExecutionRestService = _ExecutionRestService_;
        });

        StepController = $controller(BaseController, {
            $element,
            ExecutionRestService
        });
    });

    afterEach(() => {
        restoreFeedback();
        restoreUpdater();
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
            spyOn(ExecutionRestService, "updateStepStatus").and.returnValue($q.when());
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
                    "passed"
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
                ExecutionRestService.updateStepStatus.and.returnValue(
                    $q.reject("This user cannot update the execution")
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
                    "failed"
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
                    "blocked"
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
                    "notrun"
                );
                expect(StepController.isNotRun()).toBe(true);
                expect(StepController.saving).toBe(false);
                expect(StepController.dropdown.hide).toHaveBeenCalled();
            });
        });
    });
});

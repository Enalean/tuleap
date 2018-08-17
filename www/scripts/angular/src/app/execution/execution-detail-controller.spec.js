import execution_module from "./execution.js";
import angular from "angular";
import "angular-mocks";

import BaseController from "./execution-detail-controller.js";

describe("ExecutionDetailController -", () => {
    let $scope,
        $q,
        SharedPropertiesService,
        ExecutionService,
        TlpModalService,
        NewTuleapArtifactModalService,
        ExecutionRestService;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller, $rootScope;

        angular.mock.inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _SharedPropertiesService_,
            _ExecutionService_,
            _TlpModalService_,
            _NewTuleapArtifactModalService_,
            _ExecutionRestService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            SharedPropertiesService = _SharedPropertiesService_;
            ExecutionService = _ExecutionService_;
            TlpModalService = _TlpModalService_;
            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            ExecutionRestService = _ExecutionRestService_;
        });

        $scope = $rootScope.$new();

        spyOn(SharedPropertiesService, "getIssueTrackerConfig").and.returnValue({
            permissions: {
                create: true,
                link: true
            },
            xref_color: "acid-green"
        });

        spyOn(ExecutionService, "loadExecutions");

        $controller(BaseController, {
            $scope,
            ExecutionService,
            TlpModalService,
            NewTuleapArtifactModalService,
            ExecutionRestService
        });
    });

    describe("showLinkToNewBugModal() -", () => {
        it("when the callback is called from the modal, then the new issue will be linked to the execution and then will be shown in an alert and added to the linked issues dropdown", function() {
            const artifact = {
                id: 68,
                title: "Xanthomelanoi Kate",
                xref: "bugs #68",
                tracker: {
                    id: 4
                }
            };
            $scope.execution = {
                id: 51,
                definition: {
                    summary: "syrinx",
                    description: "topping"
                },
                previous_result: {
                    result: null
                }
            };
            $scope.campaign = {
                label: "shirtless"
            };
            spyOn(NewTuleapArtifactModalService, "showCreation").and.callFake(
                (tracker_id, b, callback, prefill_values) => {
                    callback(artifact.id);
                }
            );
            spyOn(ExecutionRestService, "linkIssueWithoutComment").and.returnValue($q.when());
            spyOn(ExecutionRestService, "getArtifactById").and.returnValue($q.when(artifact));
            spyOn(ExecutionService, "addArtifactLink");

            $scope.showLinkToNewBugModal();

            $scope.$apply();
            expect($scope.linkedIssueId).toBe(artifact.id);
            expect($scope.linkedIssueAlertVisible).toBe(true);
            expect(artifact.tracker.color_name).toBe("acid-green");
            expect(ExecutionService.addArtifactLink).toHaveBeenCalledWith(
                $scope.execution.id,
                artifact
            );
        });
    });

    describe("showLinkToExistingBugModal() -", () => {
        it("when the callback is called from the modal, then the linked issue will be shown in an alert and will be added to the linked issues dropdown", () => {
            const artifact = {
                id: 70,
                title: "phalangean authorcraft",
                xref: "bugs #70"
            };
            $scope.execution = { id: 26 };
            spyOn(TlpModalService, "open").and.callFake(({ resolve }) => {
                resolve.modal_callback(artifact);
            });
            spyOn(ExecutionService, "addArtifactLink");

            $scope.showLinkToExistingBugModal();

            expect(TlpModalService.open).toHaveBeenCalled();
            expect($scope.linkedIssueId).toBe(artifact.id);
            expect($scope.linkedIssueAlertVisible).toBe(true);
            expect(ExecutionService.addArtifactLink).toHaveBeenCalledWith(
                $scope.execution.id,
                artifact
            );
        });
    });

    describe("Status updates", () => {
        const user = { id: 626 };
        const execution = {
            id: 8,
            status: "notrun",
            time: "",
            results: "psychoanalyzer rupture solidish"
        };
        const time = 570;

        beforeEach(() => {
            spyOn(SharedPropertiesService, "getCurrentUser").and.returnValue(user);
            spyOn(ExecutionService, "updateTestExecution");
            spyOn(ExecutionRestService, "putTestExecution").and.returnValue($q.when(execution));
            $scope.execution = execution;
            $scope.timer = { execution_time: time };
        });

        describe("pass()", () => {
            it("Then the status will be saved to 'passed' and the timer will be reset", () => {
                $scope.pass(execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "passed",
                    null,
                    execution.results
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect($scope.timer.execution_time).toEqual(0);
            });

            it("When there is a problem with the update, then the error will be shown on the execution", () => {
                const error = { status: 500 };
                ExecutionRestService.putTestExecution.and.returnValue($q.reject(error));
                spyOn(ExecutionService, "displayError");

                $scope.pass(execution);
                $scope.$apply();

                expect(ExecutionService.displayError).toHaveBeenCalledWith(execution, error);
            });
        });

        describe("fail()", () => {
            it("Then the status will be saved to 'failed' and the timer will be reset", () => {
                $scope.fail(execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "failed",
                    null,
                    execution.results
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect($scope.timer.execution_time).toEqual(0);
            });
        });

        describe("block()", () => {
            it("Then the status will be saved to 'blocked' and the timer will be reset", () => {
                $scope.block(execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "blocked",
                    null,
                    execution.results
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect($scope.timer.execution_time).toEqual(0);
            });
        });

        describe("notrun()", () => {
            it("Then the status will be saved to 'notrun' and the timer will be reset", () => {
                $scope.notrun(execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "notrun",
                    null,
                    execution.results
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect($scope.timer.execution_time).toEqual(0);
            });
        });
    });
});

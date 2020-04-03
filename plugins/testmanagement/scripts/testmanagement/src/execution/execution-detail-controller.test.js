/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

        angular.mock.inject(function (
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

        jest.spyOn(SharedPropertiesService, "getIssueTrackerConfig").mockReturnValue({
            permissions: {
                create: true,
                link: true,
            },
            xref_color: "acid-green",
        });

        jest.spyOn(ExecutionService, "loadExecutions").mockImplementation(() => {});

        $controller(BaseController, {
            $scope,
            ExecutionService,
            TlpModalService,
            NewTuleapArtifactModalService,
            ExecutionRestService,
        });
    });

    describe("showLinkToNewBugModal() -", () => {
        it("when the callback is called from the modal, then the new issue will be linked to the execution and then will be shown in an alert and added to the linked issues dropdown", function () {
            const artifact = {
                id: 68,
                title: "Xanthomelanoi Kate",
                xref: "bugs #68",
                tracker: {
                    id: 4,
                },
            };
            $scope.execution = {
                id: 51,
                definition: {
                    summary: "syrinx",
                    description: "topping",
                },
                previous_result: {
                    result: null,
                },
            };
            $scope.campaign = {
                label: "shirtless",
            };
            jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(
                (tracker_id, b, callback) => {
                    callback(artifact.id);
                }
            );
            jest.spyOn(ExecutionRestService, "linkIssueWithoutComment").mockReturnValue($q.when());
            jest.spyOn(ExecutionRestService, "getArtifactById").mockReturnValue($q.when(artifact));
            jest.spyOn(ExecutionService, "addArtifactLink").mockImplementation(() => {});

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
                xref: "bugs #70",
            };
            $scope.execution = { id: 26 };
            jest.spyOn(TlpModalService, "open").mockImplementation(({ resolve }) => {
                resolve.modal_callback(artifact);
            });
            jest.spyOn(ExecutionService, "addArtifactLink").mockImplementation(() => {});

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
            results: "psychoanalyzer rupture solidish",
        };
        const time = 570;

        beforeEach(() => {
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockReturnValue(user);
            jest.spyOn(ExecutionService, "updateTestExecution").mockImplementation(() => {});
            jest.spyOn(ExecutionRestService, "putTestExecution").mockReturnValue(
                $q.when(execution)
            );
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
                ExecutionRestService.putTestExecution.mockReturnValue($q.reject(error));
                jest.spyOn(ExecutionService, "displayError").mockImplementation(() => {});

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

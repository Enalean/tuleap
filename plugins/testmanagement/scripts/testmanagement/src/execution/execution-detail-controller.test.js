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
        $state,
        $q,
        SharedPropertiesService,
        ExecutionService,
        TlpModalService,
        NewTuleapArtifactModalService,
        ckeditorGetData,
        ExecutionRestService,
        ExecutionDetailController;

    const user = { id: 626 };
    beforeEach(() => {
        angular.mock.module(execution_module);
        ckeditorGetData = {};
        ckeditorGetData.getData = () => "";

        let $controller, $rootScope;

        angular.mock.inject(
            function (
                _$controller_,
                _$q_,
                _$rootScope_,
                _SharedPropertiesService_,
                _ExecutionService_,
                _TlpModalService_,
                _NewTuleapArtifactModalService_,
                _ExecutionRestService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                SharedPropertiesService = _SharedPropertiesService_;
                ExecutionService = _ExecutionService_;
                TlpModalService = _TlpModalService_;
                NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
                ExecutionRestService = _ExecutionRestService_;
            },
        );

        $state = {
            params: {},
        };

        $scope = $rootScope.$new();

        jest.spyOn(ExecutionRestService, "leaveTestExecution").mockImplementation(() => $q.when());

        jest.spyOn(SharedPropertiesService, "getIssueTrackerConfig").mockReturnValue({
            permissions: {
                create: true,
                link: true,
            },
            xref_color: "acid-green",
        });

        jest.spyOn(ExecutionService, "loadExecutions").mockImplementation(() => {});

        ExecutionDetailController = $controller(BaseController, {
            $scope,
            $state,
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
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockReturnValue(user);
            jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(
                (user_id, tracker_id, b, callback) => {
                    callback(artifact.id);
                },
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
                artifact,
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
                artifact,
            );
        });
    });

    describe("Status updates", () => {
        const execution = {
            id: 8,
            status: "notrun",
            time: "",
            results: "psychoanalyzer rupture solidish",
            previous_result: { result: "old comment" },
        };
        const event = { target: {} };

        beforeEach(() => {
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockReturnValue(user);
            jest.spyOn(ExecutionService, "updateTestExecution").mockImplementation(() => {});
            jest.spyOn(ExecutionService, "setCommentOnEditor").mockImplementation(() => {});
            jest.spyOn(ExecutionService, "viewTestExecutionIfRTEAlreadyExists").mockImplementation(
                () => {},
            );
            jest.spyOn(ExecutionRestService, "putTestExecution").mockReturnValue(
                $q.when(execution),
            );
            ExecutionService.editor = ckeditorGetData;
            execution.results = "psychoanalyzer rupture solidish";
            execution.uploaded_files_through_text_field = [];
            execution.uploaded_files_through_attachment_area = [];
            execution.removed_files = [];
            $scope.execution = execution;
            $scope.displayTestCommentEditor = true;
        });

        describe("pass()", () => {
            it("Then the status will be saved to 'passed'", () => {
                $scope.pass(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "passed",
                    execution.results,
                    [],
                    [],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
            });

            it("When there is a problem with the update, then the error will be shown on the execution", () => {
                const error = {
                    code: 400,
                    message: "error",
                };

                ExecutionRestService.putTestExecution.mockReturnValue($q.reject(error));
                jest.spyOn(ExecutionService, "displayErrorMessage").mockImplementation(() => {});

                $scope.pass(event, execution);
                $scope.$apply();

                expect(ExecutionService.displayErrorMessage).toHaveBeenCalledWith(
                    execution,
                    "error",
                );
            });

            it("When there is no comment, Then the comment box is created and displayed with previous comment", () => {
                execution.results = "";

                $scope.pass(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "passed",
                    execution.results,
                    [],
                    [],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect(ExecutionService.viewTestExecutionIfRTEAlreadyExists).toHaveBeenCalledWith(
                    execution.id,
                    user,
                );
                expect(ExecutionService.setCommentOnEditor).toHaveBeenCalledWith("old comment");
                expect($scope.displayTestCommentEditor).toBeTruthy();
            });

            it("When the comment has not been changed, Then warning is displayed", () => {
                execution.results = "old comment";
                $scope.displayTestCommentEditor = false;

                $scope.pass(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "passed",
                    execution.results,
                    [],
                    [],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect($scope.displayTestCommentEditor).toBeFalsy();
                expect($scope.onlyStatusHasBeenChanged).toBeTruthy();
            });

            it("When the comment has not been changed, and warning is displayed, and user accept the message, Then the warning is not anymore displayed", () => {
                execution.results = "old comment";
                $scope.displayTestCommentEditor = false;

                $scope.pass(event, execution);
                $scope.$apply();

                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
                expect($scope.onlyStatusHasBeenChanged).toBeTruthy();

                $scope.userIsAcceptingThatOnlyStatusHasBeenChanged();
                expect($scope.onlyStatusHasBeenChanged).toBeFalsy();
            });
        });

        describe("fail()", () => {
            it("Then the status will be saved to 'failed'", () => {
                $scope.fail(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "failed",
                    execution.results,
                    [],
                    [],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
            });
        });

        describe("block()", () => {
            it("Then the status will be saved to 'blocked'", () => {
                $scope.block(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "blocked",
                    execution.results,
                    [],
                    [],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
            });
        });

        describe("notrun()", () => {
            beforeEach(() => {
                execution.uploaded_files_through_attachment_area = [
                    {
                        id: 15,
                        filename: "bug_1.png",
                        upload_error_message: "",
                        progress: 100,
                    },
                    {
                        id: 16,
                        filename: "bug_2.png",
                        upload_error_message: "Upload is fucked up",
                        progress: 100,
                    },
                ];
                execution.removed_files = [
                    {
                        id: 18,
                        filename: "bug_18.png",
                    },
                ];
            });

            it("Then the status will be saved to 'notrun'", () => {
                ckeditorGetData.getData = () => ["/download/href"];
                execution.uploaded_files_through_text_field = [
                    {
                        id: 13,
                        download_href: "/download/href",
                    },
                ];

                $scope.notrun(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "notrun",
                    execution.results,
                    [13, 15],
                    [18],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
            });

            it("Then the status will be saved to 'notrun' and only the file in ckeditor will be send", () => {
                ckeditorGetData.getData = () => ["/download/href"];
                execution.uploaded_files_through_text_field = [
                    {
                        id: 13,
                        download_href: "/download/href",
                    },
                    {
                        id: 14,
                        download_href: "/download/otherhref",
                    },
                ];

                $scope.notrun(event, execution);
                $scope.$apply();

                expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                    execution.id,
                    "notrun",
                    execution.results,
                    [13, 15],
                    [18],
                );
                expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
            });
        });
    });

    describe("Update the comment", () => {
        const user = { id: 626 };
        const status = "passed";
        const execution = {
            id: 8,
            status,
            time: "",
            results: "new comment",
            previous_result: { result: "old comment" },
        };
        const event = { target: {} };

        beforeEach(() => {
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockReturnValue(user);
            jest.spyOn(ExecutionService, "updateTestExecution").mockImplementation(() => {});
            jest.spyOn(ExecutionRestService, "putTestExecution").mockReturnValue(
                $q.when(execution),
            );
            ExecutionService.editor = ckeditorGetData;
            $scope.execution = execution;
            $scope.displayTestCommentEditor = true;
        });

        it("When the user updates the comment, Then the status does not change", () => {
            execution.uploaded_files_through_text_field = [];
            execution.uploaded_files_through_attachment_area = [];
            execution.removed_files = [];

            $scope.updateComment(event, execution);
            $scope.$apply();

            expect(ExecutionRestService.putTestExecution).toHaveBeenCalledWith(
                execution.id,
                status,
                execution.results,
                [],
                [],
            );
            expect(ExecutionService.updateTestExecution).toHaveBeenCalledWith(execution, user);
            expect(execution.status).toEqual(status);
        });
    });

    describe("reload-comment-editor-view", () => {
        const execution = {
            id: 8,
            status,
            time: "",
            results: "",
            previous_result: { result: "", submitted_by: { id: 666 } },
            removed_files: [],
        };
        beforeEach(() => {
            execution.previous_result.result = "";
            $scope.displayTestCommentEditor = true;
            jest.spyOn(ExecutionService, "getDataInEditor").mockImplementation(
                () => "A comment in editing mode",
            );
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockImplementation(() => {
                return { id: 120 };
            });
            jest.spyOn(ExecutionService, "clearEditor").mockImplementation(() => {});
            jest.spyOn(
                ExecutionService,
                "clearFilesUploadedThroughAttachmentArea",
            ).mockImplementation(() => {});
        });

        it("When there is no comment in editing mode and no new comment, Then the editor is cleared and write mode is displayed", () => {
            jest.spyOn(ExecutionService, "getDataInEditor").mockImplementation(() => "");

            $state.params.execid = execution.id;
            ExecutionDetailController.$onInit();

            $scope.$emit("reload-comment-editor-view", execution);

            expect(ExecutionService.clearEditor).toHaveBeenCalled();
            expect(ExecutionService.clearFilesUploadedThroughAttachmentArea).toHaveBeenCalled();
            expect($scope.displayTestCommentEditor).toBeTruthy();
        });
        it("When there is no comment in editing mode but a new comment, Then the editor is cleared and read mode is displayed", () => {
            jest.spyOn(ExecutionService, "getDataInEditor").mockImplementation(() => "");
            execution.previous_result.result = "A new comment";

            $state.params.execid = execution.id;
            ExecutionDetailController.$onInit();

            $scope.$emit("reload-comment-editor-view", execution);

            expect(ExecutionService.clearEditor).toHaveBeenCalled();
            expect(ExecutionService.clearFilesUploadedThroughAttachmentArea).toHaveBeenCalled();
            expect($scope.displayTestCommentEditor).toBeFalsy();
        });
        it("When the user who pushed the new comment and the user who was editing the comment are same, Then the editor is cleared", () => {
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockImplementation(() => {
                return { id: 666 };
            });

            $state.params.execid = execution.id;
            ExecutionDetailController.$onInit();

            $scope.$emit("reload-comment-editor-view", execution);

            expect(ExecutionService.clearEditor).toHaveBeenCalled();
            expect(ExecutionService.clearFilesUploadedThroughAttachmentArea).toHaveBeenCalled();
            expect($scope.displayTestCommentEditor).toBeTruthy();
        });
        it("When comment is being edited and editor is displayed and users are different, Then warning message is displayed", () => {
            $state.params.execid = execution.id;
            ExecutionDetailController.$onInit();

            $scope.$emit("reload-comment-editor-view", execution);

            expect(ExecutionService.clearEditor).not.toHaveBeenCalled();
            expect(ExecutionService.clearFilesUploadedThroughAttachmentArea).not.toHaveBeenCalled();
            expect($scope.displayTestCommentEditor).toBeTruthy();
            expect($scope.displayTestCommentWarningOveriding).toBeTruthy();
            expect(execution.results).toBe("A comment in editing mode");
        });
        it("When the user is commenting a test and someone else is passing another one, Then warning message is NOT displayed", () => {
            $state.params.execid = 66;
            ExecutionDetailController.$onInit();

            $scope.$emit("reload-comment-editor-view", execution);

            expect(ExecutionService.clearEditor).not.toHaveBeenCalled();
            expect(ExecutionService.clearFilesUploadedThroughAttachmentArea).not.toHaveBeenCalled();
            expect($scope.displayTestCommentEditor).toBeTruthy();
            expect($scope.displayTestCommentWarningOveriding).toBeFalsy();
        });
    });
});

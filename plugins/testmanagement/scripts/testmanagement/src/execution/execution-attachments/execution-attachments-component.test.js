/*
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

import angular from "angular";
import "angular-mocks";
import execution_module from "../execution.js";
import * as attachments_uploader from "./execution-attachments-uploader.js";
import * as tlp from "tlp";

jest.mock("./execution-attachments-uploader");
jest.mock("tlp");

describe("execution-attachments-component", () => {
    let controller,
        $scope,
        $q,
        $element,
        $root_scope,
        ExecutionService,
        ExecutionRestService,
        execution;

    beforeEach(() => {
        angular.mock.module(execution_module);
        angular.mock.inject(
            (
                $componentController,
                $rootScope,
                _$q_,
                _ExecutionService_,
                _ExecutionRestService_,
            ) => {
                $q = _$q_;
                $scope = $rootScope.$new();
                $root_scope = $rootScope;
                ExecutionService = _ExecutionService_;
                ExecutionRestService = _ExecutionRestService_;

                $element = angular.element("<div></div>");

                execution = {
                    id: 1260,
                    upload_url: "/api/v1/tracker_fields/7756/file",
                    uploaded_files_through_attachment_area: [],
                };

                controller = $componentController(
                    "executionAttachments",
                    {
                        $scope,
                        $element,
                        $q,
                        $rootScope,
                        ExecutionService,
                        ExecutionRestService,
                    },
                    {
                        execution,
                        isInCommentMode: true,
                    },
                );
            },
        );
    });

    describe("initialisation", () => {
        it("should watch the file input to appear to add the change event listener on it", () => {
            controller.$onInit();

            const file_input = document.createElement("input");
            file_input.setAttribute("type", "file");
            file_input.setAttribute("id", "test-files-upload-button");

            jest.spyOn(file_input, "addEventListener");

            $element[0].appendChild(file_input);

            $scope.$digest();

            expect(file_input.addEventListener).toHaveBeenCalledWith(
                "change",
                expect.any(Function),
            );
        });

        it("does nothing when there is no upload url on the execution", () => {
            execution.upload_url = null;

            controller.$onInit();

            const file_input = document.createElement("input");
            file_input.setAttribute("type", "file");
            file_input.setAttribute("id", "test-files-upload-button");

            jest.spyOn(file_input, "addEventListener");

            $element[0].appendChild(file_input);

            $scope.$digest();

            expect(file_input.addEventListener).not.toHaveBeenCalledWith(
                "change",
                expect.any(Function),
            );
        });
    });

    describe("$onDestroy", () => {
        it("should destroy all the popover instances", () => {
            const popover_1 = { destroy: jest.fn() };
            const popover_2 = { destroy: jest.fn() };
            const popover_3 = { destroy: jest.fn() };

            controller.upload_error_messages_popovers = new Map([
                [1, popover_1],
                [2, popover_2],
                [3, popover_3],
            ]);

            controller.$onDestroy();

            expect(popover_1.destroy).toHaveBeenCalled();
            expect(popover_2.destroy).toHaveBeenCalled();
            expect(popover_3.destroy).toHaveBeenCalled();
        });
    });

    describe("attachFile()", () => {
        it("attaches a file to the current test execution", () => {
            const new_file = {
                id: 1234,
                upload_href: "/upload-me.here",
                download_href: "/download-me.there",
            };

            jest.spyOn(ExecutionRestService, "createFileInTestExecution").mockReturnValue(
                $q.when(new_file),
            );
            jest.spyOn(ExecutionService, "addToFilesAddedThroughAttachmentArea").mockImplementation(
                () => {},
            );
            jest.spyOn(attachments_uploader, "processUpload");

            const file_to_attach = {
                name: "bug.png",
                size: 12345678910,
                type: "image/png",
            };

            controller.attachFile(file_to_attach);

            $scope.$digest();

            expect(ExecutionRestService.createFileInTestExecution).toHaveBeenCalledWith(execution, {
                name: file_to_attach.name,
                file_size: file_to_attach.size,
                file_type: file_to_attach.type,
            });
            expect(ExecutionService.addToFilesAddedThroughAttachmentArea).toHaveBeenCalledWith(
                execution,
                {
                    id: new_file.id,
                    filename: file_to_attach.name,
                    progress: 0,
                    upload_error_message: "",
                    upload_url: "/upload-me.here",
                },
            );
            expect(attachments_uploader.processUpload).toHaveBeenCalledWith(
                file_to_attach,
                new_file.upload_href,
                expect.any(Function),
                expect.any(Function),
            );
        });

        it("does not try to upload empty files", () => {
            const new_file = {
                id: 1234,
                upload_href: "",
                download_href: "/download-me.there",
            };

            jest.spyOn(ExecutionRestService, "createFileInTestExecution").mockReturnValue(
                $q.when(new_file),
            );
            jest.spyOn(ExecutionService, "addToFilesAddedThroughAttachmentArea").mockImplementation(
                () => {},
            );
            jest.spyOn(attachments_uploader, "processUpload");

            const file_to_attach = {
                name: "empty.txt",
                size: 0,
                type: "text/plain",
            };

            controller.attachFile(file_to_attach);

            $scope.$digest();

            expect(ExecutionRestService.createFileInTestExecution).toHaveBeenCalledWith(execution, {
                name: file_to_attach.name,
                file_size: file_to_attach.size,
                file_type: file_to_attach.type,
            });
            expect(ExecutionService.addToFilesAddedThroughAttachmentArea).toHaveBeenCalledWith(
                execution,
                {
                    id: new_file.id,
                    filename: file_to_attach.name,
                    progress: 100,
                    upload_error_message: "",
                    upload_url: null,
                },
            );
            expect(attachments_uploader.processUpload).not.toHaveBeenCalled();
        });

        it("forbids to upload two files with the same name for the same comment", () => {
            jest.spyOn(ExecutionRestService, "createFileInTestExecution").mockReturnValue(
                $q.when({ id: 101 }),
            );
            jest.spyOn(ExecutionService, "addToFilesAddedThroughAttachmentArea");
            jest.spyOn(
                ExecutionService,
                "doesFileAlreadyExistInUploadedAttachments",
            ).mockReturnValue(true);
            jest.spyOn(attachments_uploader, "processUpload");

            const file_to_attach = {
                name: "bug.png",
                size: 12345678910,
                type: "image/png",
            };

            controller.attachFile(file_to_attach);

            $scope.$digest();

            expect(ExecutionRestService.createFileInTestExecution).toHaveBeenCalled();
            expect(ExecutionService.addToFilesAddedThroughAttachmentArea).not.toHaveBeenCalled();
            expect(attachments_uploader.processUpload).not.toHaveBeenCalled();
            expect(Array.from(controller.file_creation_errors)).toEqual([
                {
                    filename: "bug.png",
                    message: "This file has already been attached to this comment",
                },
            ]);
        });
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe("abortUpload()", () => {
        it("should abort the upload and remove the attachment from the list", () => {
            const file_uploading = {
                id: 1234,
                filename: "bug.png",
                progress: 25,
                upload_url: "/upload-me.here",
            };
            execution.uploaded_files_through_attachment_area.push();

            jest.spyOn(attachments_uploader, "abortFileUpload").mockReturnValue($q.when());

            controller.abortUpload(file_uploading);

            expect(attachments_uploader.abortFileUpload).toHaveBeenCalledWith(
                file_uploading.upload_url,
            );
            expect(execution.uploaded_files_through_attachment_area).toHaveLength(0);
        });
    });

    describe("addFileToRemovedFiles()", () => {
        it("remove attachment file", () => {
            const removed_file = {
                id: 1234,
                name: "bug.png",
                is_deleted: false,
            };
            const event = { preventDefault: jest.fn() };

            jest.spyOn(ExecutionService, "addFileToDeletedFiles").mockImplementation(() => {});

            controller.addFileToRemovedFiles(event, removed_file);

            $scope.$digest();

            expect(event.preventDefault).toHaveBeenCalled();
            expect(ExecutionService.addFileToDeletedFiles).toHaveBeenCalledWith(execution, {
                id: removed_file.id,
                name: removed_file.name,
                is_deleted: true,
            });

            expect(removed_file.is_deleted).toBeTruthy();
        });
    });

    describe("cancelFileRemoval()", () => {
        it("deletes file from removed files", () => {
            const removed_file = {
                id: 1234,
                name: "bug.png",
                is_deleted: true,
            };
            const event = { preventDefault: jest.fn() };

            jest.spyOn(ExecutionService, "removeFileFromDeletedFiles").mockImplementation(() => {});

            controller.cancelFileRemoval(event, removed_file);

            $scope.$digest();

            expect(event.preventDefault).toHaveBeenCalled();
            expect(ExecutionService.removeFileFromDeletedFiles).toHaveBeenCalledWith(execution, {
                id: removed_file.id,
                name: removed_file.name,
                is_deleted: false,
            });

            expect(removed_file.is_deleted).toBeFalsy();
        });
    });

    describe("handleUploadError()", () => {
        it("should create a popover and update the upload state", () => {
            const uploading_file = {
                id: 110,
                progress: 25,
                upload_error_message: "",
            };

            jest.spyOn(tlp, "createPopover");
            jest.spyOn(ExecutionService, "updateExecutionAttachment");

            controller.handleUploadError(uploading_file, new Error("Upload is fucked up"));

            expect(tlp.createPopover).toHaveBeenCalled();
            expect(ExecutionService.updateExecutionAttachment).toHaveBeenCalledWith(
                execution,
                110,
                {
                    progress: 100,
                    upload_error_message: "Upload is fucked up",
                },
            );
        });
    });

    describe("removeAttachmentFromList", () => {
        it("should remove the file upload and delete its error message popover", () => {
            jest.spyOn(ExecutionService, "removeFileUploadedThroughAttachmentArea");

            const popover_110 = { destroy: jest.fn() };

            controller.upload_error_messages_popovers = new Map([[110, popover_110]]);

            controller.removeAttachmentFromList({ id: 110 });

            expect(ExecutionService.removeFileUploadedThroughAttachmentArea).toHaveBeenCalledWith(
                execution,
                110,
            );
            expect(popover_110.destroy).toHaveBeenCalled();
        });
    });

    describe("Rest error handling", () => {
        it("should store file creation errors in a list and should clear it when the error modal have been closed", () => {
            jest.spyOn(ExecutionRestService, "createFileInTestExecution").mockReturnValue(
                $q.reject({
                    code: 400,
                    message: "File too big",
                }),
            );

            controller.$onInit();
            controller.attachFile({
                name: "bug.png",
                size: 12345678910,
                type: "image/png",
            });

            $scope.$digest();

            expect(controller.file_creation_errors).toEqual([
                {
                    filename: "bug.png",
                    message: "File too big",
                },
            ]);

            $scope.$emit("user-has-closed-the-file-creation-errors-modal");
            $scope.$digest();

            expect(controller.file_creation_errors).toEqual([]);
        });
    });

    describe("Dropped files", () => {
        it("Uploads dropped files", () => {
            const new_file = {
                id: 1234,
                upload_href: "/upload-me.here",
                download_href: "/download-me.there",
            };

            const file_to_attach = {
                name: "bug.png",
                size: 12345678910,
                type: "image/png",
            };

            jest.spyOn(ExecutionRestService, "createFileInTestExecution").mockReturnValue(
                $q.when(new_file),
            );
            jest.spyOn(ExecutionService, "addToFilesAddedThroughAttachmentArea").mockImplementation(
                () => {},
            );
            jest.spyOn(attachments_uploader, "processUpload");

            controller.$onInit();

            $root_scope.$emit("execution-attachments-dropped", { files: [file_to_attach] });
            $scope.$digest();

            expect(ExecutionRestService.createFileInTestExecution).toHaveBeenCalledWith(execution, {
                name: file_to_attach.name,
                file_size: file_to_attach.size,
                file_type: file_to_attach.type,
            });

            expect(ExecutionService.addToFilesAddedThroughAttachmentArea).toHaveBeenCalledWith(
                execution,
                {
                    id: new_file.id,
                    filename: file_to_attach.name,
                    progress: 0,
                    upload_error_message: "",
                    upload_url: "/upload-me.here",
                },
            );

            expect(attachments_uploader.processUpload).toHaveBeenCalledWith(
                file_to_attach,
                new_file.upload_href,
                expect.any(Function),
                expect.any(Function),
            );
        });

        it("does not upload items that are not files", () => {
            controller.$onInit();

            jest.spyOn(ExecutionRestService, "createFileInTestExecution");
            jest.spyOn(ExecutionService, "addToFilesAddedThroughAttachmentArea");
            jest.spyOn(attachments_uploader, "processUpload");

            $root_scope.$emit("execution-attachments-dropped", {
                files: [
                    {
                        name: "Screenshots",
                        size: 4096,
                        type: "",
                    },
                ],
            });
            $scope.$digest();

            expect(ExecutionRestService.createFileInTestExecution).not.toHaveBeenCalled();
            expect(ExecutionService.addToFilesAddedThroughAttachmentArea).not.toHaveBeenCalled();
            expect(attachments_uploader.processUpload).not.toHaveBeenCalled();

            expect(controller.file_creation_errors).toEqual([
                {
                    filename: "Screenshots",
                    message: "This item is not a file",
                },
            ]);
        });
    });
});

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

jest.mock("./execution-attachments-uploader");

describe("execution-attachments-component", () => {
    let controller, $scope, $q, $element, ExecutionService, ExecutionRestService, execution;

    beforeEach(() => {
        angular.mock.module(execution_module);
        angular.mock.inject(
            (
                $componentController,
                $rootScope,
                _$q_,
                _ExecutionService_,
                _ExecutionRestService_
            ) => {
                $q = _$q_;
                $scope = $rootScope.$new();
                ExecutionService = _ExecutionService_;
                ExecutionRestService = _ExecutionRestService_;

                $element = angular.element("<div></div>");

                execution = {
                    id: 1260,
                    upload_url: "/api/v1/tracker_fields/7756/file",
                };

                controller = $componentController(
                    "executionAttachments",
                    {
                        $scope,
                        $element,
                        $q,
                        ExecutionService,
                        ExecutionRestService,
                    },
                    {
                        execution,
                        isInCommentMode: true,
                    }
                );
            }
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
                expect.any(Function)
            );
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
                $q.when(new_file)
            );
            jest.spyOn(ExecutionService, "addToFilesAddedThroughAttachmentArea").mockImplementation(
                () => {}
            );
            jest.spyOn(attachments_uploader, "processUpload");

            const file_to_attach = {
                name: "bug.png",
                size: 12345678910,
                type: "image/png",
            };

            controller.attachFile(file_to_attach);

            $scope.$digest();

            expect(ExecutionRestService.createFileInTestExecution).toHaveBeenCalledWith(
                execution,
                file_to_attach
            );
            expect(ExecutionService.addToFilesAddedThroughAttachmentArea).toHaveBeenCalledWith(
                execution,
                {
                    id: new_file.id,
                    filename: file_to_attach.name,
                }
            );
            expect(attachments_uploader.processUpload).toHaveBeenCalledWith(
                file_to_attach,
                new_file.upload_href
            );
        });
    });
});

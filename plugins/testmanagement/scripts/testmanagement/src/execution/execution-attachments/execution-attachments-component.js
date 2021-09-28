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

import "./execution-attachments.tpl.html";
import { processUpload, abortFileUpload } from "./execution-attachments-uploader.js";

export default {
    bindings: {
        execution: "<",
        isInCommentMode: "<",
    },
    templateUrl: "execution-attachments.tpl.html",
    controller,
};

controller.$inject = ["$scope", "$element", "$q", "ExecutionService", "ExecutionRestService"];

function controller($scope, $element, $q, ExecutionService, ExecutionRestService) {
    const self = this;

    Object.assign(self, {
        $onInit,
        attachFile,
        abortUpload,
    });

    function $onInit() {
        $scope.$watch(
            () => getFileInput(),
            (file_input) => {
                if (!file_input) {
                    return;
                }

                file_input.addEventListener("change", () => {
                    for (const file of file_input.files) {
                        attachFile(file);
                    }
                });
            }
        );
    }

    function attachFile(file) {
        ExecutionRestService.createFileInTestExecution(self.execution, file).then((new_file) => {
            const file_uploading = {
                id: new_file.id,
                filename: file.name,
                upload_url: new_file.upload_href,
                progress: 0,
            };

            ExecutionService.addToFilesAddedThroughAttachmentArea(self.execution, file_uploading);

            processUpload(file, new_file.upload_href, (progress_in_percent) => {
                ExecutionService.updateExecutionAttachment(self.execution, file_uploading.id, {
                    progress: progress_in_percent,
                });
                $scope.$apply();
            });
        });
    }

    function getFileInput() {
        return $element[0].querySelector("#test-files-upload-button");
    }

    function abortUpload(file_uploading) {
        abortFileUpload(file_uploading.upload_url).then(() => {
            ExecutionService.removeFileUploadedThroughAttachmentArea(
                self.execution,
                file_uploading.id
            );
            $scope.$apply();
        });
    }
}

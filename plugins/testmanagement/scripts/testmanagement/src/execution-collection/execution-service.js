/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import {
    buildFileUploadHandler,
    isThereAnImageWithDataURI,
    MaxSizeUploadExceededError,
    UploadError,
} from "@tuleap/ckeditor-image-upload";
import CKEDITOR from "ckeditor4";
// eslint-disable-next-line you-dont-need-lodash-underscore/for-each, you-dont-need-lodash-underscore/filter, you-dont-need-lodash-underscore/some
import { extend, filter, forEach, has, remove, some } from "lodash-es";
import prettyKibibytes from "pretty-kibibytes";

export default ExecutionService;

ExecutionService.$inject = [
    "$q",
    "$rootScope",
    "ExecutionConstants",
    "ExecutionRestService",
    "SharedPropertiesService",
    "gettextCatalog",
];

function ExecutionService(
    $q,
    $rootScope,
    ExecutionConstants,
    ExecutionRestService,
    SharedPropertiesService,
    gettextCatalog,
) {
    const self = this;

    Object.assign(self, {
        initialization,
        synchronizeExecutions,
        loadExecutions,
        getAllRemoteExecutions,
        getExecutionsByDefinitionId,
        updateExecutionToUseLatestVersionOfDefinition,
        updateCampaign,
        addTestExecution,
        addTestExecutionWithoutUpdateCampaignStatus,
        removeTestExecution,
        removeTestExecutionWithoutUpdateCampaignStatus,
        updateTestExecution,
        clearEditor,
        clearFilesUploadedThroughAttachmentArea,
        getUsedUploadedFilesIds,
        updatePresencesOnCampaign,
        removeAllPresencesOnCampaign,
        viewTestExecution,
        viewTestExecutionIfRTEAlreadyExists,
        removeAllViewTestExecution,
        removeViewTestExecution,
        removeViewTestExecutionByUUID,
        displayPresencesForAllExecutions,
        displayPresencesByExecution,
        displayErrorMessage,
        executionsForCampaign,
        addArtifactLink,
        setCommentOnEditor,
        getDataInEditor,
        addToFilesAddedThroughAttachmentArea,
        getUploadedFilesThroughAttachmentAreaIds,
        updateExecutionAttachment,
        removeFileUploadedThroughAttachmentArea,
        addFileToDeletedFiles,
        removeFileFromDeletedFiles,
        getFilesIdToRemove,
        clearRemovedFiles,
        hasFileBeingUploaded,
        doesFileAlreadyExistInUploadedAttachments,
        updateTestExecutionNoBy,
    });

    initialization();

    function initialization() {
        extend(self, {
            UNCATEGORIZED: ExecutionConstants.UNCATEGORIZED,
            campaign: {},
            executions_by_categories_by_campaigns: {},
            executions: {},
            editor: null,
            categories: {},
            loading: {},
            presences_loaded: false,
            executions_loaded: false,
            presences_by_execution: {},
            presences_on_campaign: [],
        });
    }

    function synchronizeExecutions(campaign_id) {
        var limit = 50,
            offset = 0;

        return getAllRemoteExecutions(campaign_id, limit, offset).then(
            function (remote_executions) {
                var executions_to_remove = filter(self.executions, function (execution) {
                    return !remote_executions.some((remote) => remote.id === execution.id);
                });
                var executions_to_add = remote_executions.filter(function (execution) {
                    return !some(self.executions, { id: execution.id });
                });

                executions_to_remove.forEach(removeTestExecutionWithoutUpdateCampaignStatus);
                executions_to_add.forEach(addTestExecutionWithoutUpdateCampaignStatus);
            },
        );
    }

    function loadExecutions(campaign_id) {
        self.campaign_id = campaign_id;

        if (self.executions_by_categories_by_campaigns[campaign_id]) {
            return $q.when(Object.values(self.executions_by_categories_by_campaigns[campaign_id]));
        }

        var limit = 50,
            offset = 0;

        self.loading[campaign_id] = true;
        self.executions_by_categories_by_campaigns[campaign_id] = {};

        return getAllRemoteExecutions(campaign_id, limit, offset)
            .finally(() => {
                self.loading[campaign_id] = false;
            })
            .then((executions) => {
                updatePresencesOnCampaign();

                return executions;
            });
    }

    function getAllRemoteExecutions(campaign_id, limit, offset, remote_executions) {
        remote_executions = remote_executions || [];

        return ExecutionRestService.getRemoteExecutions(campaign_id, limit, offset).then(
            function (data) {
                var total_executions = data.total;

                groupExecutionsByCategory(campaign_id, data.results);
                $rootScope.$emit("bunch-of-executions-loaded", data.results);
                remote_executions = remote_executions.concat(data.results);

                offset = offset + limit;
                if (offset < total_executions) {
                    return getAllRemoteExecutions(campaign_id, limit, offset, remote_executions);
                }
                return remote_executions;
            },
        );
    }

    function updateExecutionToUseLatestVersionOfDefinition(execution_id) {
        ExecutionRestService.updateExecutionToUseLatestVersionOfDefinition(execution_id);
    }

    function groupExecutionsByCategory(campaign_id, executions) {
        if (self.executions_by_categories_by_campaigns[campaign_id] === undefined) {
            self.executions_by_categories_by_campaigns[campaign_id] = {};
        }
        var categories = self.executions_by_categories_by_campaigns[campaign_id];

        executions.forEach(function (execution) {
            var category = execution.definition.category;
            if (!category) {
                category = ExecutionConstants.UNCATEGORIZED;
                execution.definition._uncategorized = category;
            }

            if (!has(self.executions, execution.id)) {
                self.executions[execution.id] = execution;
            }

            if (typeof categories[category] === "undefined") {
                categories[category] = {
                    label: category,
                    executions: [],
                };
            }

            if (
                !categories[category].executions.some(
                    (category_execution) => category_execution.id === execution.id,
                )
            ) {
                categories[category].executions.push(execution);
            }
        });

        self.categories = categories;
    }

    function getExecutionsByDefinitionId(artifact_id) {
        const executions = Object.values(self.categories).flatMap(
            (category) => category.executions,
        );

        return executions.filter((execution) => execution.definition.id === artifact_id);
    }

    function addTestExecution(execution) {
        var executions = [execution];
        var status = execution.status;

        groupExecutionsByCategory(self.campaign_id, executions);
        self.campaign["nb_of_" + status]++;
        self.campaign.total++;
    }

    function addTestExecutionWithoutUpdateCampaignStatus(execution) {
        var executions = [execution];

        groupExecutionsByCategory(self.campaign_id, executions);
    }

    function removeTestExecution(execution_to_remove) {
        removeTestExecutionByCategories(execution_to_remove.id);
        self.campaign["nb_of_" + execution_to_remove.status]--;
        self.campaign.total--;
    }

    function removeTestExecutionWithoutUpdateCampaignStatus(execution_to_remove) {
        removeTestExecutionByCategories(execution_to_remove.id);
    }

    function removeTestExecutionByCategories(execution_to_remove_id) {
        for (const category of Object.values(
            self.executions_by_categories_by_campaigns[self.campaign_id],
        )) {
            remove(category.executions, { id: execution_to_remove_id });
        }
        delete self.executions[execution_to_remove_id];
    }

    function updateTestExecutionNoBy(execution_updated) {
        const execution = self.executions[execution_updated.id];
        updateTestExecutionNow(execution, execution_updated);
    }
    function updateTestExecution(execution_updated, updated_by) {
        const execution = self.executions[execution_updated.id];
        if (
            isCurrentUserViewingExecution(execution) &&
            hasDefinitionChanged(execution, execution_updated) &&
            !isCurrentUserOriginatorOfTheUpdate(updated_by)
        ) {
            execution.userCanReloadTestBecauseDefinitionIsUpdated = () => {
                delete execution.userCanReloadTestBecauseDefinitionIsUpdated;
                updateTestExecutionNow(execution, execution_updated);
            };
        } else {
            updateTestExecutionNow(execution, execution_updated);
        }
    }

    function clearEditor(execution) {
        self.editor.setData("", { noSnapshot: true });
        execution.uploaded_files_through_text_field = [];
        clearRemovedFiles(execution);
    }

    function clearRemovedFiles(execution) {
        execution.removed_files.forEach((file) => {
            file.is_deleted = false;
        });
        execution.removed_files = [];
    }

    function clearFilesUploadedThroughAttachmentArea(execution) {
        execution.uploaded_files_through_attachment_area = [];
    }

    function getDataInEditor() {
        return self.editor.getData();
    }

    function setCommentOnEditor(comment) {
        self.editor.setData(comment);
    }

    function hasDefinitionChanged(execution, execution_updated) {
        return (
            execution.definition.summary !== execution_updated.definition.summary ||
            execution.definition.description !== execution_updated.definition.description
        );
    }

    function isCurrentUserOriginatorOfTheUpdate(updated_by) {
        return updated_by.id === SharedPropertiesService.getCurrentUser().id;
    }

    function isCurrentUserViewingExecution(execution) {
        return (
            execution.viewed_by &&
            execution.viewed_by.find(
                (user) => user.uuid === SharedPropertiesService.getCurrentUser().uuid,
            )
        );
    }

    function updateTestExecutionNow(execution, execution_updated) {
        const previous_status = execution.previous_result.status;
        const status = execution_updated.status;

        Object.assign(execution, execution_updated);

        execution.previous_result.has_been_run_at_least_once = true;

        execution.submitted_by = null;
        execution.error = "";
        execution.results = "";

        self.campaign["nb_of_" + status]++;
        self.campaign["nb_of_" + previous_status]--;

        updatePresencesOnCampaign();

        $rootScope.$broadcast("reload-comment-editor-view", execution);
    }

    function updatePresencesOnCampaign() {
        const score_per_user_id = new Map();

        for (const execution of Object.values(self.executions)) {
            if (!shouldExecutionBeUsedForUserScore(execution)) {
                continue;
            }
            const user = execution.previous_result.submitted_by;
            let user_with_score = score_per_user_id.get(user.id);
            if (user_with_score === undefined) {
                user_with_score = { ...user, score: 0 };
            }
            user_with_score.score++;
            score_per_user_id.set(user.id, user_with_score);
        }

        self.presences_on_campaign = Array.from(score_per_user_id.values());
    }

    function shouldExecutionBeUsedForUserScore(execution) {
        return (
            execution.status !== "notrun" &&
            execution.definition &&
            execution.definition.automated_tests === ""
        );
    }

    function updateCampaign(new_campaign) {
        self.campaign = new_campaign;
    }

    function viewTestExecutionIfRTEAlreadyExists(execution_id, user) {
        if (CKEDITOR.instances["execution_" + execution_id]) {
            return;
        }

        viewTestExecution(execution_id, user);
    }

    function viewTestExecution(execution_id, user) {
        if (has(self.executions, execution_id)) {
            var execution = self.executions[execution_id];

            if (!has(execution, "viewed_by")) {
                execution.viewed_by = [];
            }

            const user_exists = execution.viewed_by.some(
                (presence) => presence.uuid === user.uuid && presence.id === user.id,
            );

            if (!user_exists) {
                execution.viewed_by.push(user);
            }

            waitForFieldBeforeLoadRTE(execution);
            $rootScope.$applyAsync();
        }
    }

    function waitForFieldBeforeLoadRTE(execution) {
        let field = document.getElementById("execution_" + execution.id);
        if (field) {
            loadRTE(field, execution);
        } else {
            setTimeout(function () {
                waitForFieldBeforeLoadRTE(execution);
            }, 30);
        }
    }

    function loadRTE(field, execution) {
        let additional_options = {};
        let instance = CKEDITOR.instances["execution_" + execution.id];
        if (instance) {
            CKEDITOR.remove(instance);
        }

        if (execution.upload_url) {
            additional_options = {
                extraPlugins: "uploadimage",
                uploadUrl: execution.upload_url,
                clipboard_handleImages: false,
            };
        }
        let config = {
            disableNativeSpellChecker: false,
            toolbar: [
                ["Bold", "Italic", "Styles"],
                ["Link", "Unlink", "Image"],
            ],
            stylesSet: [
                { name: "Bold", element: "strong", overrides: "b" },
                { name: "Italic", element: "em", overrides: "i" },
                { name: "Code", element: "code" },
                { name: "Subscript", element: "sub" },
                { name: "Superscript", element: "sup" },
            ],
            uiColor: "ffffff",
            language: document.body.dataset.userLocale,
            ...additional_options,
        };
        self.editor = CKEDITOR.inline(field, config);
        self.editor.on("change", function () {
            execution.results = self.editor.getData();
        });
        field.setAttribute("contenteditable", true);
        setupImageUpload(field, execution);
    }

    function setupImageUpload(field, execution) {
        execution.uploaded_files_through_text_field = [];

        if (!execution.upload_url) {
            disablePasteOfImages();
            return;
        }

        const onStartCallback = () => {};
        const onErrorCallback = (error) => {
            if (error instanceof MaxSizeUploadExceededError) {
                execution.error = gettextCatalog.getString(
                    "You are not allowed to upload images bigger than {{ max_size }}",
                    { max_size: prettyKibibytes(execution.max_size_upload) },
                );
            } else if (error instanceof UploadError) {
                execution.error = gettextCatalog.getString("Unable to upload the image");
            }
        };
        const onSuccessCallback = (id, download_href) => {
            addToFilesAddedByTextField(execution, { id, download_href });
        };

        const fileUploadRequestHandler = buildFileUploadHandler({
            ckeditor_instance: self.editor,
            max_size_upload: execution.max_size_upload,
            onStartCallback,
            onErrorCallback,
            onSuccessCallback,
        });

        self.editor.on("fileUploadRequest", fileUploadRequestHandler, null, null, 4);
    }

    function getUsedUploadedFilesIds(execution) {
        let upload_files_id = [];
        execution.uploaded_files_through_text_field.forEach(function (item) {
            if (self.editor.getData().indexOf(item.download_href) !== -1) {
                upload_files_id.push(item.id);
            }
        });
        return upload_files_id;
    }

    function doesFileAlreadyExistInUploadedAttachments(execution, file) {
        return execution.uploaded_files_through_attachment_area.some(
            (attachment) => attachment.id === file.id,
        );
    }

    function disablePasteOfImages() {
        self.editor.on("paste", (event) => {
            if (isThereAnImageWithDataURI(event.data.dataValue)) {
                event.data.dataValue = "";
                event.cancel();
                self.editor.showNotification(
                    gettextCatalog.getString("You are not allowed to paste images here"),
                );
            }
        });
    }

    function addToFilesAddedByTextField(execution, uploaded_file) {
        execution.uploaded_files_through_text_field.push(uploaded_file);
    }

    function addToFilesAddedThroughAttachmentArea(execution, uploaded_file) {
        execution.uploaded_files_through_attachment_area.push(uploaded_file);
    }

    function getUploadedFilesThroughAttachmentAreaIds(execution) {
        const files_to_upload_ids = [];

        execution.uploaded_files_through_attachment_area.forEach((file) => {
            if (file.upload_error_message.length === 0 && file.progress === 100) {
                files_to_upload_ids.push(file.id);
            }
        });

        return files_to_upload_ids;
    }

    function hasFileBeingUploaded(execution) {
        return execution.uploaded_files_through_attachment_area.some((file) => file.progress < 100);
    }

    function updateExecutionAttachment(execution, attachment_id, attachment_attributes) {
        const attachment = execution.uploaded_files_through_attachment_area.find(
            (attachment) => attachment.id === attachment_id,
        );
        if (!attachment) {
            return;
        }

        Object.assign(attachment, attachment_attributes);
    }

    function removeFileUploadedThroughAttachmentArea(execution, attachment_id) {
        const index = execution.uploaded_files_through_attachment_area.findIndex(
            (attachment) => attachment.id === attachment_id,
        );
        if (index === -1) {
            return;
        }

        execution.uploaded_files_through_attachment_area.splice(index, 1);
    }

    function addFileToDeletedFiles(execution, removed_file) {
        execution.removed_files.push(removed_file);
    }

    function removeFileFromDeletedFiles(execution, removed_file) {
        execution.removed_files = execution.removed_files.filter(
            (files) => files.id !== removed_file.id,
        );
    }

    function getFilesIdToRemove(execution) {
        return execution.removed_files.map((file) => file.id);
    }

    function removeViewTestExecution(execution_id, user_to_remove) {
        if (has(self.executions, execution_id)) {
            remove(self.executions[execution_id].viewed_by, function (user) {
                return user.id === user_to_remove.id && user.uuid === user_to_remove.uuid;
            });
            if (self.executions[execution_id].userCanReloadTestBecauseDefinitionIsUpdated) {
                self.executions[execution_id].userCanReloadTestBecauseDefinitionIsUpdated();
            }
        }
    }

    function removeAllViewTestExecution() {
        forEach(self.executions, function (execution) {
            remove(execution.viewed_by);
        });
    }

    function removeViewTestExecutionByUUID(uuid) {
        forEach(self.executions, function (execution) {
            remove(execution.viewed_by, { uuid: uuid });
        });
    }

    function removeAllPresencesOnCampaign() {
        self.presences_on_campaign = [];
    }

    function displayPresencesByExecution(execution_id, presences) {
        if (has(self.executions, execution_id)) {
            self.executions[execution_id].viewed_by = presences;
        }
    }

    function displayPresencesForAllExecutions() {
        if (self.presences_loaded && self.executions_loaded) {
            self.presences_loaded = false;
            self.executions_loaded = false;

            forEach(self.presences_by_execution, function (presences, execution_id) {
                forEach(presences, function (presence) {
                    viewTestExecution(execution_id, presence);
                });
            });
        }
    }

    function displayErrorMessage(execution, message) {
        execution.error = message;
    }

    function executionsForCampaign(campaign_id) {
        return Object.values(self.executions_by_categories_by_campaigns[campaign_id]).flatMap(
            (executions_by_categories) => executions_by_categories.executions,
        );
    }

    function addArtifactLink(execution_id, artifact_link) {
        if (!has(self.executions, execution_id)) {
            return;
        }
        const execution = self.executions[execution_id];

        if (!execution.linked_bugs.some((bug) => bug.id === artifact_link.id)) {
            execution.linked_bugs.push(artifact_link);
        }
    }
}

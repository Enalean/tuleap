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

import _ from "lodash";
import CKEDITOR from "ckeditor4";
import prettyKibibytes from "pretty-kibibytes";

import {
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "../../../../../../src/scripts/tuleap/ckeditor/file-upload-handler-factory";
import { isThereAnImageWithDataURI } from "../../../../../../src/scripts/tuleap/ckeditor/image-urls-finder.js";

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
    gettextCatalog
) {
    const self = this;

    Object.assign(self, {
        initialization,
        synchronizeExecutions,
        loadExecutions,
        getAllRemoteExecutions,
        getExecutionsByDefinitionId,
        updateExecutionToUseLatestVersionOfDefinition,
        addPresenceCampaign,
        updateCampaign,
        addTestExecution,
        addTestExecutionWithoutUpdateCampaignStatus,
        removeTestExecution,
        removeTestExecutionWithoutUpdateCampaignStatus,
        updateTestExecution,
        clearEditor,
        getUsedUploadedFilesIds,
        updatePresenceOnCampaign,
        removeAllPresencesOnCampaign,
        viewTestExecution,
        removeAllViewTestExecution,
        removeViewTestExecution,
        removeViewTestExecutionByUUID,
        displayPresencesForAllExecutions,
        displayPresencesByExecution,
        displayError,
        displayErrorMessage,
        executionsForCampaign,
        addArtifactLink,
    });

    initialization();

    function initialization() {
        _.extend(self, {
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

        return getAllRemoteExecutions(campaign_id, limit, offset).then(function (
            remote_executions
        ) {
            //eslint-disable-next-line you-dont-need-lodash-underscore/select
            var executions_to_remove = _.select(self.executions, function (execution) {
                return !remote_executions.some((remote) => remote.id === execution.id);
            });
            var executions_to_add = remote_executions.filter(function (execution) {
                //eslint-disable-next-line you-dont-need-lodash-underscore/some
                return !_.some(self.executions, { id: execution.id });
            });

            executions_to_remove.forEach(removeTestExecutionWithoutUpdateCampaignStatus);
            executions_to_add.forEach(addTestExecutionWithoutUpdateCampaignStatus);
        });
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

        return getAllRemoteExecutions(campaign_id, limit, offset).finally(() => {
            self.loading[campaign_id] = false;
        });
    }

    function getAllRemoteExecutions(campaign_id, limit, offset, remote_executions) {
        remote_executions = remote_executions || [];

        return ExecutionRestService.getRemoteExecutions(campaign_id, limit, offset).then(function (
            data
        ) {
            var total_executions = data.total;

            groupExecutionsByCategory(campaign_id, data.results);
            $rootScope.$emit("bunch-of-executions-loaded", data.results);
            remote_executions = remote_executions.concat(data.results);

            offset = offset + limit;
            if (offset < total_executions) {
                return getAllRemoteExecutions(campaign_id, limit, offset, remote_executions);
            }
            return remote_executions;
        });
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

            if (!_.has(self.executions, execution.id)) {
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
                    (category_execution) => category_execution.id === execution.id
                )
            ) {
                categories[category].executions.push(execution);
            }
        });

        self.categories = categories;
    }

    function getExecutionsByDefinitionId(artifact_id) {
        //eslint-disable-next-line you-dont-need-lodash-underscore/map
        var executions = [].concat(..._.map(self.categories, "executions"));

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
            self.executions_by_categories_by_campaigns[self.campaign_id]
        )) {
            _.remove(category.executions, { id: execution_to_remove_id });
        }
        delete self.executions[execution_to_remove_id];
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
        self.editor.setData("");
        execution.uploaded_files = [];
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
                (user) => user.uuid === SharedPropertiesService.getCurrentUser().uuid
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
    }

    function updatePresenceOnCampaign(user) {
        var user_on_campaign = self.presences_on_campaign.find(
            (presence) => presence.id === user.id
        );

        if (user_on_campaign && !_.has(user_on_campaign, "score")) {
            _.extend(user_on_campaign, user.score);
        }

        if (user_on_campaign && user_on_campaign.score !== user.score) {
            user_on_campaign.score = user.score;
        }

        if (!user_on_campaign) {
            addPresenceCampaign(user);
        }
    }

    function updateCampaign(new_campaign) {
        self.campaign = new_campaign;
    }

    function addPresenceCampaign(user) {
        var user_id_exists = self.presences_on_campaign.some((presence) => presence.id === user.id);

        if (!user_id_exists) {
            self.presences_on_campaign.push(user);
        } else if (_.has(user, "score")) {
            _.extend(user_id_exists, user.score);
        }
    }

    function viewTestExecution(execution_id, user) {
        if (_.has(self.executions, execution_id)) {
            var execution = self.executions[execution_id];

            if (!_.has(execution, "viewed_by")) {
                execution.viewed_by = [];
            }

            var user_uuid_exists = execution.viewed_by.some(
                (presence) => presence.uuid === user.uuid
            );

            if (!user_uuid_exists) {
                execution.viewed_by.push(user);
            }

            waitForFieldBeforeLoadRTE(execution);
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
        execution.uploaded_files = [];

        if (!execution.upload_url) {
            disablePasteOfImages();
            return;
        }

        const onStartCallback = () => {};
        const onErrorCallback = (error) => {
            if (error instanceof MaxSizeUploadExceededError) {
                execution.error = gettextCatalog.getString(
                    "You are not allowed to upload images bigger than {{ max_size }}",
                    { max_size: prettyKibibytes(execution.max_size_upload) }
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
        execution.uploaded_files.forEach(function (item) {
            if (self.editor.getData().indexOf(item.download_href) !== -1) {
                upload_files_id.push(item.id);
            }
        });
        return upload_files_id;
    }

    function disablePasteOfImages() {
        self.editor.on("paste", (event) => {
            if (isThereAnImageWithDataURI(event.data.dataValue)) {
                event.data.dataValue = "";
                event.cancel();
                self.editor.showNotification(
                    gettextCatalog.getString("You are not allowed to paste images here")
                );
            }
        });
    }

    function addToFilesAddedByTextField(execution, uploaded_file) {
        execution.uploaded_files.push(uploaded_file);
    }

    function removeViewTestExecution(execution_id, user_to_remove) {
        if (_.has(self.executions, execution_id)) {
            _.remove(self.executions[execution_id].viewed_by, function (user) {
                return user.id === user_to_remove.id && user.uuid === user_to_remove.uuid;
            });
            if (self.executions[execution_id].userCanReloadTestBecauseDefinitionIsUpdated) {
                self.executions[execution_id].userCanReloadTestBecauseDefinitionIsUpdated();
            }
        }
    }

    function removeAllViewTestExecution() {
        //eslint-disable-next-line you-dont-need-lodash-underscore/for-each
        _.forEach(self.executions, function (execution) {
            _.remove(execution.viewed_by);
        });
    }

    function removeViewTestExecutionByUUID(uuid) {
        //eslint-disable-next-line you-dont-need-lodash-underscore/for-each
        _.forEach(self.executions, function (execution) {
            _.remove(execution.viewed_by, { uuid: uuid });
        });
    }

    function removeAllPresencesOnCampaign() {
        self.presences_on_campaign = [];
    }

    function displayPresencesByExecution(execution_id, presences) {
        if (_.has(self.executions, execution_id)) {
            self.executions[execution_id].viewed_by = presences;
        }
    }

    function displayPresencesForAllExecutions() {
        if (self.presences_loaded && self.executions_loaded) {
            self.presences_loaded = false;
            self.executions_loaded = false;
            //eslint-disable-next-line you-dont-need-lodash-underscore/for-each
            _.forEach(self.presences_by_execution, function (presences, execution_id) {
                //eslint-disable-next-line you-dont-need-lodash-underscore/for-each
                _.forEach(presences, function (presence) {
                    viewTestExecution(execution_id, presence);
                    addPresenceCampaign(presence);
                });
            });
        }
    }

    function displayError(execution, response) {
        execution.error = response.status + ": " + response.data.error.message;
    }

    function displayErrorMessage(execution, message) {
        execution.error = message;
    }

    function executionsForCampaign(campaign_id) {
        //eslint-disable-next-line you-dont-need-lodash-underscore/map
        var executions = _.map(
            self.executions_by_categories_by_campaigns[campaign_id],
            "executions"
        );
        return [].concat(...executions);
    }

    function addArtifactLink(execution_id, artifact_link) {
        if (!_.has(self.executions, execution_id)) {
            return;
        }
        const execution = self.executions[execution_id];

        execution.linked_bugs.push(artifact_link);
    }
}

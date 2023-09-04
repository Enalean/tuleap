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

import { has, remove } from "lodash-es";

import "./execution-link-issue.tpl.html";
import ExecutionLinkIssueCtrl from "./execution-link-issue-controller.js";
import { theTestHasJustBeenUpdated } from "./execution-detail-just-updated-state.js";
import {
    PASSED_STATUS,
    FAILED_STATUS,
    BLOCKED_STATUS,
    NOT_RUN_STATUS,
} from "./execution-constants.js";
import { sprintf } from "sprintf-js";

export default ExecutionDetailCtrl;

ExecutionDetailCtrl.$inject = [
    "$scope",
    "$state",
    "$sce",
    "$rootScope",
    "gettextCatalog",
    "ExecutionService",
    "DefinitionService",
    "SharedPropertiesService",
    "ArtifactLinksGraphService",
    "ArtifactLinksGraphModalLoading",
    "NewTuleapArtifactModalService",
    "ExecutionRestService",
    "TlpModalService",
];

function ExecutionDetailCtrl(
    $scope,
    $state,
    $sce,
    $rootScope,
    gettextCatalog,
    ExecutionService,
    DefinitionService,
    SharedPropertiesService,
    ArtifactLinksGraphService,
    ArtifactLinksGraphModalLoading,
    NewTuleapArtifactModalService,
    ExecutionRestService,
    TlpModalService,
) {
    var execution_id,
        campaign_id,
        issue_config = SharedPropertiesService.getIssueTrackerConfig();

    $scope.pass = pass;
    $scope.fail = fail;
    $scope.block = block;
    $scope.notrun = notrun;
    $scope.getStatusLabel = getStatusLabel;
    $scope.updateComment = updateComment;
    $scope.linkMenuIsVisible = issue_config.permissions.create && issue_config.permissions.link;
    $scope.canCreateIssue = issue_config.permissions.create;
    $scope.canLinkIssue = issue_config.permissions.link;
    $scope.showArtifactLinksGraphModal = showArtifactLinksGraphModal;
    $scope.toggleCommentArea = toggleCommentArea;
    $scope.getToggleTitle = getToggleTitle;
    $scope.isCommentAreaExpanded = false;
    $scope.showEditArtifactModal = showEditArtifactModal;
    $scope.closeLinkedIssueAlert = closeLinkedIssueAlert;
    $scope.linkedIssueId = null;
    $scope.linkedIssueAlertVisible = false;
    $scope.displayTestCommentEditor = false;
    $scope.showTestCommentEditor = showTestCommentEditor;
    $scope.shouldCommentSectionBeDisplayed = shouldCommentSectionBeDisplayed;
    $scope.onCancelEditionComment = onCancelEditionComment;
    $scope.shouldCancelEditionCommentBeDisplayed = shouldCancelEditionCommentBeDisplayed;
    $scope.onlyStatusHasBeenChanged = false;
    $scope.onReloadTestBecauseDefinitionIsUpdated = onReloadTestBecauseDefinitionIsUpdated;
    $scope.userIsAcceptingThatOnlyStatusHasBeenChanged =
        userIsAcceptingThatOnlyStatusHasBeenChanged;
    $scope.displayTestCommentWarningOveriding = false;
    $scope.onLoadNewComment = onLoadNewComment;
    $scope.onContinueToEditComment = onContinueToEditComment;
    $scope.getWarningTestCommentHasBeenUpdatedMessage = getWarningTestCommentHasBeenUpdatedMessage;
    $scope.areFilesUploading = areFilesUploading;

    Object.assign($scope, {
        showLinkToExistingBugModal,
        showLinkToNewBugModal,
    });

    this.$onInit = initialization;

    $scope.$on("controller-reload", function () {
        initialization();
    });

    $scope.$on("reload-comment-editor-view", (event, execution) => {
        if (execution_id !== execution.id) {
            return;
        }

        const was_comment_editing = ExecutionService.getDataInEditor() !== "";
        if (
            was_comment_editing &&
            execution.previous_result.submitted_by.id !==
                SharedPropertiesService.getCurrentUser().id
        ) {
            execution.results = ExecutionService.getDataInEditor();
            $scope.displayTestCommentWarningOveriding = true;
            $scope.displayTestCommentEditor = true;
            return;
        }
        clearCommentZone(execution);
        $scope.displayTestCommentEditor = !execution.previous_result.result;
    });

    $scope.$on("$destroy", function () {
        var future_execution_id = parseInt($state.params.execid, 10);
        if (!Number.isFinite(future_execution_id)) {
            $rootScope.$broadcast("execution-detail-destroy");
            ExecutionRestService.leaveTestExecution(execution_id);
            ExecutionService.removeViewTestExecution(
                execution_id,
                SharedPropertiesService.getCurrentUser(),
            );
        }
    });

    function shouldCommentSectionBeDisplayed() {
        if (!$scope.campaign || !$scope.execution) {
            return false;
        }

        if ($scope.campaign.is_open) {
            return true;
        }

        return $scope.execution.previous_result.result !== "";
    }

    function initialization() {
        execution_id = parseInt($state.params.execid, 10);
        campaign_id = parseInt($state.params.id, 10);

        ExecutionService.loadExecutions(campaign_id);

        if (isCurrentExecutionLoaded()) {
            retrieveCurrentExecution();
        } else {
            waitForExecutionToBeLoaded();
        }

        $scope.artifact_links_graph_modal_loading = ArtifactLinksGraphModalLoading.loading;
        $scope.edit_artifact_modal_loading = NewTuleapArtifactModalService.loading;
    }

    function showLinkToNewBugModal() {
        function callback(artifact_id) {
            return ExecutionRestService.linkIssueWithoutComment(artifact_id, $scope.execution)
                .then(() => {
                    $scope.linkedIssueId = artifact_id;
                    $scope.linkedIssueAlertVisible = true;
                    return ExecutionRestService.getArtifactById(artifact_id);
                })
                .then(
                    (artifact) => {
                        artifact.tracker.color_name =
                            SharedPropertiesService.getIssueTrackerConfig().xref_color;
                        return ExecutionService.addArtifactLink($scope.execution.id, artifact);
                    },
                    () => {
                        ExecutionService.displayErrorMessage(
                            $scope.execution,
                            gettextCatalog.getString(
                                "Error while refreshing the list of linked bugs",
                            ),
                        );
                    },
                );
        }

        var current_definition = $scope.execution.definition;
        var issue_details =
            gettextCatalog.getString("Campaign") +
            " <em>" +
            $scope.campaign.label +
            "</em><br/>" +
            gettextCatalog.getString("Test summary") +
            " <em>" +
            current_definition.summary +
            "</em><br/>" +
            gettextCatalog.getString("Test description") +
            "<br/>" +
            "<blockquote>" +
            current_definition.description +
            "</blockquote>";

        if ($scope.execution.previous_result.result) {
            issue_details =
                "<p>" + $scope.execution.previous_result.result + "</p>" + issue_details;
        }

        var prefill_values = [
            {
                name: "details",
                value: issue_details,
                format: "html",
            },
        ];

        const issue_tracker_id = SharedPropertiesService.getIssueTrackerId();
        const current_user = SharedPropertiesService.getCurrentUser();
        NewTuleapArtifactModalService.showCreation(
            current_user.id,
            issue_tracker_id,
            null,
            callback,
            prefill_values,
        );
    }

    function showLinkToExistingBugModal() {
        function callback(artifact) {
            $scope.linkedIssueId = artifact.id;
            $scope.linkedIssueAlertVisible = true;
            ExecutionService.addArtifactLink($scope.execution.id, artifact);
        }

        return TlpModalService.open({
            templateUrl: "execution-link-issue.tpl.html",
            controller: ExecutionLinkIssueCtrl,
            controllerAs: "modal",
            resolve: {
                modal_model: {
                    test_execution: $scope.execution,
                },
                modal_callback: callback,
            },
        });
    }

    function closeLinkedIssueAlert() {
        $scope.linkedIssueAlertVisible = false;
    }

    function showArtifactLinksGraphModal(execution) {
        ArtifactLinksGraphService.showGraphModal(execution);
    }

    function toggleCommentArea() {
        $scope.isCommentAreaExpanded = !$scope.isCommentAreaExpanded;
    }

    function getToggleTitle() {
        return $scope.isCommentAreaExpanded
            ? gettextCatalog.getString("Collapse")
            : gettextCatalog.getString("Expand");
    }

    function showEditArtifactModal($event, definition) {
        var when_left_mouse_click = 1;

        var old_category = $scope.execution.definition.category;
        var current_user_id = SharedPropertiesService.getCurrentUser().id;

        function callback(artifact_id) {
            var executions = ExecutionService.getExecutionsByDefinitionId(artifact_id);
            ExecutionService.updateExecutionToUseLatestVersionOfDefinition($scope.execution.id);
            $scope.execution.previous_result.result = "";
            notrun($event, $scope.execution);
            theTestHasJustBeenUpdated();
            $scope.onlyStatusHasBeenChanged = false;

            return DefinitionService.getDefinitionById(artifact_id).then(function (definition) {
                executions.forEach((execution) => {
                    $scope.execution = ExecutionService.executions[execution.id];

                    $scope.execution.definition.category = definition.category;
                    $scope.execution.definition.description = definition.description;
                    $scope.execution.definition.summary = definition.summary;

                    updateExecution(definition, old_category);
                });

                retrieveCurrentExecution();
            });
        }

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();

            DefinitionService.getArtifactById(definition.id).then(function (artifact) {
                NewTuleapArtifactModalService.showEdition(
                    current_user_id,
                    artifact.tracker.id,
                    artifact.id,
                    callback,
                );
            });
        }
    }

    function waitForExecutionToBeLoaded() {
        var unbind = $rootScope.$on("bunch-of-executions-loaded", function () {
            if (isCurrentExecutionLoaded()) {
                retrieveCurrentExecution();
            }
        });
        $scope.$on("$destroy", unbind);
    }

    function retrieveCurrentExecution() {
        $scope.execution = ExecutionService.executions[execution_id];
        $scope.execution.results = "";
        $scope.execution.saving = false;
        $scope.execution.is_automated =
            Boolean($scope.execution.definition.automated_tests) &&
            $scope.execution.definition.automated_tests !== "";
        $scope.execution.uploaded_files_through_text_field = [];
        $scope.execution.uploaded_files_through_attachment_area = [];
        $scope.execution.removed_files = [];
        $scope.displayTestCommentEditor = !$scope.execution.previous_result.result;
    }

    function isCurrentExecutionLoaded() {
        return typeof ExecutionService.executions[execution_id] !== "undefined";
    }

    function updateComment(event, execution) {
        setNewStatus(event, execution, execution.status);
    }

    function pass(event, execution) {
        setNewStatus(event, execution, PASSED_STATUS);
    }

    function fail(event, execution) {
        setNewStatus(event, execution, FAILED_STATUS);
    }

    function block(event, execution) {
        setNewStatus(event, execution, BLOCKED_STATUS);
    }

    function notrun(event, execution) {
        setNewStatus(event, execution, NOT_RUN_STATUS);
    }

    function setNewStatus(event, execution, new_status) {
        $scope.displayTestCommentWarningOveriding = false;
        $scope.onlyStatusHasBeenChanged = !$scope.displayTestCommentEditor;
        execution.saving = true;
        const comment = getCommentToSave(execution);
        const has_test_comment = comment !== "";
        if (event.target instanceof HTMLElement) {
            // Firefox does not blur disabled buttons, which triggers a bug that disables keydowns and thus keyboard shortcuts (https://bugzilla.mozilla.org/show_bug.cgi?id=706773)
            event.target.blur();
        }

        let uploaded_file_ids = [].concat(
            ExecutionService.getUsedUploadedFilesIds(execution),
            ExecutionService.getUploadedFilesThroughAttachmentAreaIds(execution),
        );

        const deleted_file_ids = ExecutionService.getFilesIdToRemove(execution);

        ExecutionRestService.putTestExecution(
            execution.id,
            new_status,
            comment,
            uploaded_file_ids,
            deleted_file_ids,
        )
            .then(
                function (data) {
                    ExecutionService.updateTestExecution(
                        data,
                        SharedPropertiesService.getCurrentUser(),
                    );
                    handleCommentBox(has_test_comment, execution);
                },
                (error) => {
                    ExecutionService.displayErrorMessage(execution, error.message);
                },
            )
            .finally(function () {
                execution.saving = false;
            });
    }

    function getCommentToSave(execution) {
        if ($scope.displayTestCommentEditor) {
            return execution.results;
        }
        return execution.previous_result.result;
    }

    function handleCommentBox(has_test_comment, execution) {
        if (!has_test_comment) {
            showTestCommentEditor(execution);
            if (execution.userCanReloadTestBecauseDefinitionIsUpdated) {
                clearCommentZone(execution);
            }
        }
    }

    function onReloadTestBecauseDefinitionIsUpdated(execution) {
        $scope.onlyStatusHasBeenChanged = false;
        $scope.displayTestCommentEditor = true;
        clearCommentZone(execution);
        execution.userCanReloadTestBecauseDefinitionIsUpdated();
    }

    function userIsAcceptingThatOnlyStatusHasBeenChanged() {
        $scope.onlyStatusHasBeenChanged = false;
    }

    function getStatusLabel(status) {
        var labels = {
            passed: "Passed",
            failed: "Failed",
            blocked: "Blocked",
            notrun: "Not Run",
        };

        return labels[status];
    }

    function updateExecution(definition, old_category) {
        var category_updated = definition.category;

        if (category_updated === null) {
            category_updated = ExecutionService.UNCATEGORIZED;
        }

        if (old_category === null) {
            old_category = ExecutionService.UNCATEGORIZED;
        }

        var category_exist = categoryExists(ExecutionService.categories, category_updated);
        var execution_already_placed = executionAlreadyPlaced(
            $scope.execution,
            ExecutionService.categories,
            category_updated,
        );

        if (!execution_already_placed) {
            removeCategory(ExecutionService.categories[old_category].executions, $scope.execution);
        }

        if (category_exist && !execution_already_placed) {
            ExecutionService.categories[category_updated].executions.push($scope.execution);
        } else if (!category_exist && !execution_already_placed) {
            ExecutionService.categories[category_updated] = {
                label: category_updated,
                executions: [$scope.execution],
            };
        }
    }

    function categoryExists(categories, category_updated) {
        return has(categories, category_updated);
    }

    function executionAlreadyPlaced(scopeExecution, categories, category_updated) {
        return has(categories, function (category) {
            return has(category.executions, scopeExecution.id, category_updated);
        });
    }

    function removeCategory(executions, scopeExecution) {
        remove(executions, function (execution) {
            return execution.id === scopeExecution.id;
        });
    }

    function showTestCommentEditor(execution) {
        $scope.displayTestCommentEditor = true;
        ExecutionService.viewTestExecutionIfRTEAlreadyExists(
            execution.id,
            SharedPropertiesService.getCurrentUser(),
        );
        ExecutionService.setCommentOnEditor(execution.previous_result.result);
    }

    function shouldCancelEditionCommentBeDisplayed(execution) {
        return $scope.displayTestCommentEditor && execution.previous_result.result.length > 0;
    }

    function onCancelEditionComment(execution) {
        $scope.displayTestCommentEditor = !execution.previous_result.result;
        clearCommentZone(execution);
    }

    function onLoadNewComment(execution) {
        $scope.displayTestCommentEditor = !execution.previous_result.result;
        $scope.displayTestCommentWarningOveriding = false;
        if ($scope.displayTestCommentEditor) {
            clearCommentZone(execution);
        }
    }

    function onContinueToEditComment() {
        $scope.displayTestCommentEditor = true;
        $scope.displayTestCommentWarningOveriding = false;
    }

    function getWarningTestCommentHasBeenUpdatedMessage(execution) {
        return sprintf(
            gettextCatalog.getString(
                "The comment has been updated by %s. Do you want to continue to edit your comment, or discard it and load the new one?",
            ),
            execution.previous_result.submitted_by.real_name,
        );
    }

    function areFilesUploading(execution) {
        return ExecutionService.hasFileBeingUploaded(execution);
    }

    function clearCommentZone(execution) {
        ExecutionService.clearEditor(execution);
        ExecutionService.clearFilesUploadedThroughAttachmentArea(execution);
        ExecutionService.clearRemovedFiles(execution);
    }
}

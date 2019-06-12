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

import { contains } from "lodash";
import { isInCreationMode } from "./modal-creation-mode-state.js";
import { setError, hasError, getErrorMessage } from "./rest/rest-error-state.js";
import { createArtifact, editArtifact, getFollowupsComments } from "./rest/rest-service.js";
import {
    getAllFileFields,
    isThereAtLeastOneFileField
} from "./tuleap-artifact-modal-fields/file-field/file-field-detector.js";
import { loadTooltips } from "tuleap-core/codendi/Tooltip.js";
import { uploadAllTemporaryFiles } from "./tuleap-artifact-modal-fields/file-field/file-uploader.js";

export default ArtifactModalController;

ArtifactModalController.$inject = [
    "$q",
    "$scope",
    "$timeout",
    "modal_instance",
    "modal_model",
    "gettextCatalog",
    "displayItemCallback",
    "TuleapArtifactModalValidateService",
    "TuleapArtifactModalLoading",
    "TuleapArtifactModalFieldDependenciesService"
];

function ArtifactModalController(
    $q,
    $scope,
    $timeout,
    modal_instance,
    modal_model,
    gettextCatalog,
    displayItemCallback,
    TuleapArtifactModalValidateService,
    TuleapArtifactModalLoading,
    TuleapArtifactModalFieldDependenciesService
) {
    const self = this,
        user_id = modal_model.user_id;

    Object.assign(self, {
        $onInit: init,
        artifact_id: modal_model.artifact_id,
        color: formatColor(modal_model.color),
        creation_mode: isInCreationMode(),
        ordered_fields: modal_model.ordered_fields,
        parent: null,
        parent_artifact_id: modal_model.parent_artifact_id,
        title:
            modal_model.title.content !== undefined ? modal_model.title.content : modal_model.title,
        tracker: modal_model.tracker,
        values: modal_model.values,
        followups_comments: {
            content: [],
            loading_comments: true,
            invert_order: modal_model.invert_followups_comments_order ? "asc" : "desc"
        },
        new_followup_comment: {
            body: "",
            format: modal_model.text_fields_format
        },
        formatColor,
        getDropdownAttribute,
        getRestErrorMessage: getErrorMessage,
        hasRestError: hasError,
        isDisabled,
        isFollowupCommentFormDisplayed,
        isNewParentAlertShown,
        isThereAtLeastOneFileField: () => isThereAtLeastOneFileField(Object.values(self.values)),
        setupTooltips,
        submit,
        setFieldValue,
        setFollowupComment,
        toggleFieldset
    });

    function init() {
        setFieldDependenciesWatchers();

        TuleapArtifactModalLoading.loading = false;
        self.setupTooltips();

        if (!isInCreationMode()) {
            fetchFollowupsComments(self.artifact_id, 50, 0, self.followups_comments.invert_order);
        }
    }

    function setupTooltips() {
        $timeout(function() {
            loadTooltips();
        }, 0);
    }

    function isFollowupCommentFormDisplayed() {
        return !isInCreationMode() && user_id !== 0;
    }

    function fetchFollowupsComments(artifact_id, limit, offset, order) {
        return $q
            .when(getFollowupsComments(artifact_id, limit, offset, order))
            .then(function(data) {
                self.followups_comments.content = self.followups_comments.content.concat(
                    data.results
                );

                if (offset + limit < data.total) {
                    fetchFollowupsComments(artifact_id, limit, offset + limit, order);
                } else {
                    self.followups_comments.loading_comments = false;
                    self.setupTooltips();
                }
            });
    }

    function submit() {
        TuleapArtifactModalLoading.loading = true;

        uploadAllFileFields()
            .then(function() {
                var validated_values = TuleapArtifactModalValidateService.validateArtifactFieldsValues(
                    self.values,
                    isInCreationMode()
                );

                var promise;
                if (isInCreationMode()) {
                    promise = createArtifact(modal_model.tracker_id, validated_values);
                } else {
                    promise = editArtifact(
                        modal_model.artifact_id,
                        validated_values,
                        self.new_followup_comment
                    );
                }

                return $q.when(promise);
            })
            .then(function(new_artifact) {
                modal_instance.tlp_modal.hide();

                return displayItemCallback(new_artifact.id);
            })
            .catch(() => {
                if (hasError()) {
                    return;
                }
                setError(
                    gettextCatalog.getString(
                        "An error occured while saving the artifact. Please check your network connection."
                    )
                );
            })
            .finally(function() {
                TuleapArtifactModalLoading.loading = false;
            });
    }

    function uploadAllFileFields() {
        const promises = getAllFileFields(Object.values(self.values)).map(file_field_value =>
            uploadFileField(file_field_value)
        );

        return $q.all(promises);
    }

    function uploadFileField(file_field_value) {
        const promise = $q.when(uploadAllTemporaryFiles(file_field_value.temporary_files)).then(
            temporary_files_ids => {
                const uploaded_files_ids = temporary_files_ids.filter(id => Number.isInteger(id));

                file_field_value.value = file_field_value.value.concat(uploaded_files_ids);
            },
            error => {
                if (isUploadQuotaExceeded(error)) {
                    setError(
                        gettextCatalog.getString(
                            "You exceeded your current file upload quota. Please remove existing temporary files or wait until they are cleaned up."
                        )
                    );
                    throw error;
                }

                const { file_name } = error;
                setError(
                    gettextCatalog.getString(
                        "An error occured while uploading this file: {{ file_name }}. Please check your network connection.",
                        { file_name }
                    )
                );
                throw error;
            }
        );

        return promise;
    }
    function isUploadQuotaExceeded(error) {
        return (
            error.code === 406 &&
            error.hasOwnProperty("message") &&
            error.message.includes("You exceeded your quota")
        );
    }

    function isDisabled(field) {
        var necessary_permission = isInCreationMode() ? "create" : "update";
        return !contains(field.permissions, necessary_permission);
    }

    function getDropdownAttribute(field) {
        return self.isDisabled(field) ? "" : "dropdown";
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = !fieldset.collapsed;
    }

    function formatColor(color) {
        return color.split("_").join("-");
    }

    function setFieldDependenciesWatchers() {
        TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions(
            self.tracker,
            setFieldDependenciesWatcher
        );
    }

    function setFieldDependenciesWatcher(source_field_id, target_field, field_dependencies_rules) {
        $scope.$watch(
            function() {
                return self.values[source_field_id].bind_value_ids;
            },
            function(new_value, old_value) {
                if (new_value === old_value) {
                    return;
                }

                var source_value_ids = [].concat(new_value);

                changeTargetFieldPossibleValuesAndResetSelectedValue(
                    source_field_id,
                    source_value_ids,
                    target_field,
                    field_dependencies_rules
                );
            },
            true
        );
    }

    function changeTargetFieldPossibleValuesAndResetSelectedValue(
        source_field_id,
        source_value_ids,
        target_field,
        field_dependencies_rules
    ) {
        target_field.filtered_values = TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues(
            source_value_ids,
            target_field,
            field_dependencies_rules
        );

        var target_field_selected_value = modal_model.values[target_field.field_id].bind_value_ids;
        emptyArray(target_field_selected_value);

        if (target_field.filtered_values.length === 1) {
            target_field_selected_value.push(target_field.filtered_values[0].id);
        }
    }

    function emptyArray(array) {
        array.length = 0;
    }

    function isNewParentAlertShown() {
        return isInCreationMode() && self.parent;
    }

    function setFieldValue(field_id) {
        return value => {
            self.values[field_id].value = value;
        };
    }

    function setFollowupComment(value) {
        self.new_followup_comment = value;
    }
}

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

import { loadTooltips } from "@tuleap/tooltip";
import { isInCreationMode } from "./modal-creation-mode-state.js";
import { getErrorMessage, hasError, setError } from "./rest/rest-error-state";
import { isDisabled } from "./fields/disabled-field-detector";
import {
    createArtifact,
    editArtifact,
    editArtifactWithConcurrencyChecking,
    getFollowupsComments,
} from "./rest/rest-service";
import {
    getAllFileFields,
    isThereAtLeastOneFileField,
} from "./fields/file-field/file-field-detector";
import {
    isUploadingInCKEditor,
    setIsNotUploadingInCKEditor,
} from "./fields/file-field/is-uploading-in-ckeditor-state";
import { uploadAllTemporaryFiles } from "./fields/file-field/file-uploader.js";
import {
    relativeDatePlacement,
    relativeDatePreference,
} from "../../../../../../src/scripts/tuleap/custom-elements/relative-date/relative-date-helper";
import moment from "moment";
import { formatFromPhpToMoment } from "@tuleap/date-helper";
import {
    getTargetFieldPossibleValues,
    setUpFieldDependenciesActions,
} from "./field-dependencies-helper.js";
import { validateArtifactFieldsValues } from "./validate-artifact-field-value.js";
import { TuleapAPIClient } from "./adapters/REST/TuleapAPIClient";
import { ParentFeedbackController } from "./adapters/UI/feedback/ParentFeedbackController";
import { LinkFieldController } from "./adapters/UI/fields/link-field-v2/LinkFieldController";
import { DatePickerInitializer } from "./adapters/UI/fields/date-field/DatePickerInitializer";
import { LinksRetriever } from "./domain/fields/link-field-v2/LinksRetriever";
import { CurrentArtifactIdentifierProxy } from "./adapters/Caller/CurrentArtifactIdentifierProxy";
import { ParentArtifactIdentifierProxy } from "./adapters/Caller/ParentArtifactIdentifierProxy";
import { ParentRetriever } from "./domain/parent/ParentRetriever";
import { LinksMarkedForRemovalStore } from "./adapters/Memory/LinksMarkedForRemovalStore";
import { LinksStore } from "./adapters/Memory/LinksStore";
import { ReadonlyDateFieldFormatter } from "./adapters/UI/fields/date-readonly-field/readonly-date-field-formatter";
import { FileUploadQuotaController } from "./adapters/UI/footer/FileUploadQuotaController";
import { UserTemporaryFileQuotaStore } from "./adapters/Memory/UserTemporaryFileQuotaStore";
import { LinkFieldValueFormatter } from "./domain/fields/link-field-v2/LinkFieldValueFormatter";
import { FileFieldController } from "./adapters/UI/fields/file-field/FileFieldController";
import { TrackerShortnameProxy } from "./adapters/Caller/TrackerShortnameProxy";
import { FaultFeedbackController } from "./adapters/UI/feedback/FaultFeedbackController";
import { ArtifactCrossReference } from "./domain/ArtifactCrossReference";

export default ArtifactModalController;

ArtifactModalController.$inject = [
    "$q",
    "$scope",
    "$timeout",
    "modal_instance",
    "modal_model",
    "gettextCatalog",
    "displayItemCallback",
    "TuleapArtifactModalLoading",
];

function ArtifactModalController(
    $q,
    $scope,
    $timeout,
    modal_instance,
    modal_model,
    gettextCatalog,
    displayItemCallback,
    TuleapArtifactModalLoading
) {
    const self = this,
        user_id = modal_model.user_id;
    let confirm_action_to_edit = false;
    const concurrency_error_code = 412;

    const fault_feedback_controller = FaultFeedbackController();
    const api_client = TuleapAPIClient();
    const links_store = LinksStore();
    const links_marked_for_removal_store = LinksMarkedForRemovalStore();
    const current_artifact_identifier = CurrentArtifactIdentifierProxy.fromModalArtifactId(
        modal_model.artifact_id
    );

    Object.assign(self, {
        $onInit: init,
        artifact_id: modal_model.artifact_id,
        current_artifact_identifier,
        color: formatColor(modal_model.color),
        creation_mode: isInCreationMode(),
        ordered_fields: modal_model.ordered_fields,
        parent: null,
        parent_artifact_id: modal_model.parent_artifact_id,
        title: getTitle(),
        tracker: modal_model.tracker,
        values: modal_model.values,
        is_list_picker_enabled: modal_model.is_list_picker_enabled,
        is_links_field_v2_enabled: modal_model.is_links_field_v2_enabled,
        has_current_project_parents: modal_model.has_current_project_parents,
        followups_comments: {
            content: [],
            loading_comments: true,
            invert_order: modal_model.invert_followups_comments_order ? "asc" : "desc",
        },
        new_followup_comment: {
            body: "",
            format: modal_model.text_fields_format,
        },
        link_field_value_formatter: LinkFieldValueFormatter(
            links_store,
            links_marked_for_removal_store
        ),
        date_picker_initializer: DatePickerInitializer(),
        readonly_date_field_formatter: ReadonlyDateFieldFormatter(
            document.body.dataset.userLocale ?? "en_US"
        ),
        parent_feedback_controller: ParentFeedbackController(
            ParentRetriever(api_client),
            fault_feedback_controller,
            ParentArtifactIdentifierProxy.fromCallerArgument(modal_model.parent_artifact_id)
        ),
        fault_feedback_controller,
        file_upload_quota_controller: FileUploadQuotaController(UserTemporaryFileQuotaStore()),
        getLinkFieldController: (field) => {
            return LinkFieldController(
                LinksRetriever(api_client, api_client, links_store),
                links_store,
                links_marked_for_removal_store,
                links_marked_for_removal_store,
                links_marked_for_removal_store,
                fault_feedback_controller,
                field,
                current_artifact_identifier,
                ArtifactCrossReference.fromCurrentArtifact(
                    current_artifact_identifier,
                    TrackerShortnameProxy.fromString(modal_model.tracker.item_name)
                )
            );
        },
        getFileFieldController: (field) => {
            return FileFieldController(field, self.values[field.field_id]);
        },
        hidden_fieldsets: extractHiddenFieldsets(modal_model.ordered_fields),
        formatColor,
        getDropdownAttribute,
        getRestErrorMessage: getErrorMessage,
        hasRestError: hasError,
        isDisabled,
        isFollowupCommentFormDisplayed,
        isUploadingInCKEditor,
        isThereAtLeastOneFileField: () => isThereAtLeastOneFileField(Object.values(self.values)),
        setupTooltips,
        submit,
        reopenFieldsetsWithInvalidInput,
        setFieldValueForCustomElement,
        setFieldValueForRadioButtonsCustomElement,
        setFieldValueForComputedFieldElement,
        addToFilesAddedByTextField,
        setFollowupComment,
        toggleFieldset,
        hasHiddenFieldsets,
        showHiddenFieldsets,
        relativeDatePreference: () => relativeDatePreference(modal_model.relative_dates_display),
        relativeDatePlacement: () =>
            relativeDatePlacement(modal_model.relative_dates_display, "right"),
        formatDateUsingPreferredUserFormat: (date) =>
            moment(date).format(formatFromPhpToMoment(document.body.dataset.dateTimeFormat)),
        user_locale: document.body.dataset.userLocale,
        confirm_action_to_edit,
        getButtonText,
    });

    function getButtonText() {
        if (self.confirm_action_to_edit) {
            return gettextCatalog.getString("Confirm to apply your modifications");
        }

        return gettextCatalog.getString("Save changes");
    }

    function init() {
        setFieldDependenciesWatchers();

        modal_instance.tlp_modal.addEventListener("tlp-modal-hidden", setIsNotUploadingInCKEditor);
        TuleapArtifactModalLoading.loading = false;
        self.setupTooltips();

        if (!isInCreationMode()) {
            fetchFollowupsComments(self.artifact_id, 50, 0, self.followups_comments.invert_order);
        }
    }

    function setupTooltips() {
        $timeout(function () {
            loadTooltips();
        }, 0);
    }

    function getTitle() {
        if (modal_model.title === null) {
            return "";
        }

        const is_title_a_text_field = typeof modal_model.title.content !== "undefined";

        return is_title_a_text_field ? modal_model.title.content : modal_model.title;
    }

    function isFollowupCommentFormDisplayed() {
        return !isInCreationMode() && user_id !== 0;
    }

    function fetchFollowupsComments(artifact_id, limit, offset, order) {
        return $q
            .when(getFollowupsComments(artifact_id, limit, offset, order))
            .then(function (data) {
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

    function reopenFieldsetsWithInvalidInput(form) {
        const closed_fieldsets_that_contain_invalid_elements = form.querySelectorAll(
            "fieldset.tlp-pane-collapsed:invalid > div > legend"
        );
        for (const fieldset of closed_fieldsets_that_contain_invalid_elements) {
            if (fieldset instanceof HTMLElement) {
                fieldset.click();
            }
        }
    }

    function submit() {
        if (isUploadingInCKEditor() || TuleapArtifactModalLoading.loading) {
            return;
        }
        TuleapArtifactModalLoading.loading = true;

        uploadAllFileFields()
            .then(function () {
                const validated_values = validateArtifactFieldsValues(
                    self.values,
                    isInCreationMode(),
                    self.new_followup_comment,
                    self.is_links_field_v2_enabled,
                    self.link_field_value_formatter
                );

                let promise;
                if (isInCreationMode()) {
                    promise = createArtifact(modal_model.tracker_id, validated_values);
                } else {
                    if (self.confirm_action_to_edit) {
                        promise = editArtifact(
                            modal_model.artifact_id,
                            validated_values,
                            self.new_followup_comment
                        );
                    } else {
                        promise = editArtifactWithConcurrencyChecking(
                            modal_model.artifact_id,
                            validated_values,
                            self.new_followup_comment,
                            modal_model.etag,
                            modal_model.last_modified
                        );
                    }
                }

                return $q.when(promise);
            })
            .then(function (new_artifact) {
                modal_instance.tlp_modal.hide();

                return displayItemCallback(new_artifact.id);
            })
            .catch(async (e) => {
                if (hasError()) {
                    return;
                }
                await errorHandler(e);
            })
            .finally(function () {
                TuleapArtifactModalLoading.loading = false;
            });
    }

    async function errorHandler(error) {
        try {
            const error_json = await error.response.json();

            if (
                error_json !== undefined &&
                error_json.error &&
                error_json.error.code === concurrency_error_code
            ) {
                setError(
                    gettextCatalog.getString(
                        "Someone updated this artifact while you were editing it. Please note that your modifications will be applied on top of previous changes. You need to confirm your action to submit your modification."
                    )
                );
                self.confirm_action_to_edit = true;
            } else {
                setError(gettextCatalog.getString("An error occurred while saving the artifact."));
            }
        } catch {
            setError(gettextCatalog.getString("An error occurred while saving the artifact."));
        }
        TuleapArtifactModalLoading.loading = false;
    }

    function uploadAllFileFields() {
        const promises = getAllFileFields(Object.values(self.values)).map((file_field_value) =>
            uploadFileField(file_field_value)
        );

        return $q.all(promises);
    }

    function uploadFileField(file_field_value) {
        const promise = $q.when(uploadAllTemporaryFiles(file_field_value.temporary_files)).then(
            (temporary_files_ids) => {
                const uploaded_files_ids = temporary_files_ids.filter((id) => Number.isInteger(id));

                file_field_value.value = file_field_value.value.concat(uploaded_files_ids);
            },
            (error) => {
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
                        "An error occurred while uploading this file: {{ file_name }}.",
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
            Object.prototype.hasOwnProperty.call(error, "message") &&
            error.message.includes("You exceeded your quota")
        );
    }

    function getDropdownAttribute(field) {
        return isDisabled(field) ? "" : "dropdown";
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = !fieldset.collapsed;
    }

    function formatColor(color) {
        return color.split("_").join("-");
    }

    function setFieldDependenciesWatchers() {
        setUpFieldDependenciesActions(self.tracker, setFieldDependenciesWatcher);
    }

    function setFieldDependenciesWatcher(source_field_id, target_field, field_dependencies_rules) {
        if (self.values[source_field_id] === undefined) {
            return;
        }

        $scope.$watch(
            function () {
                return self.values[source_field_id].bind_value_ids;
            },
            function (new_value, old_value) {
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
        target_field.filtered_values = getTargetFieldPossibleValues(
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

    function setFieldValueForCustomElement(event) {
        const { field_id, value } = event.detail;
        self.values[field_id].value = value;
    }

    function setFieldValueForRadioButtonsCustomElement(event) {
        const { field_id, value } = event.detail;
        self.values[field_id].bind_value_ids[0] = value;
    }

    function setFieldValueForComputedFieldElement(event) {
        const { field_id, autocomputed, manual_value } = event.detail;
        self.values[field_id].is_autocomputed = autocomputed;
        self.values[field_id].manual_value = manual_value;
    }

    function addToFilesAddedByTextField(event) {
        const { field_id, image: uploaded_file } = event.detail;
        const value_model = self.values[field_id];
        value_model.value = [uploaded_file.id].concat(value_model.value);
        value_model.images_added_by_text_fields = [uploaded_file].concat(
            value_model.images_added_by_text_fields
        );
    }

    function setFollowupComment(event) {
        self.new_followup_comment = event.detail;
    }

    function extractHiddenFieldsets(fields) {
        if (isInCreationMode() === true) {
            return [];
        }

        return fields.filter((field) => field.is_hidden);
    }

    function hasHiddenFieldsets() {
        return self.hidden_fieldsets.length > 0;
    }

    function showHiddenFieldsets(is_visible) {
        self.hidden_fieldsets.forEach(function (field) {
            field.is_hidden = !is_visible;
        });
    }
}

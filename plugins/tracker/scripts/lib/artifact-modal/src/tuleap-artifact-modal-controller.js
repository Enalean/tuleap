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
import { Option } from "@tuleap/option";
import { en_US_LOCALE } from "@tuleap/core-constants";
import { EVENT_TLP_MODAL_WILL_HIDE } from "@tuleap/tlp-modal";
import {
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import {
    ArtifactCrossReference,
    LinkFieldCreator,
    LinkFieldValueFormatter,
    LinksMarkedForRemovalStore,
    LinksStore,
    NewLinksStore,
    ParentTrackerIdentifier,
    TrackerShortname,
    UserIdentifier,
} from "@tuleap/plugin-tracker-link-field";
import { isInCreationMode } from "./modal-creation-mode-state.ts";
import { getErrorMessage, hasError, setError } from "./rest/rest-error-state";
import { isDisabled } from "./adapters/UI/fields/disabled-field-detector";
import { editArtifact, editArtifactWithConcurrencyChecking } from "./rest/rest-service";
import { getAllFileFields } from "./adapters/UI/fields/file-field/file-field-detector";
import { validateArtifactFieldsValues } from "./validate-artifact-field-value.js";
import { TuleapAPIClient } from "./adapters/REST/TuleapAPIClient";
import { ParentFeedbackController } from "./domain/parent/ParentFeedbackController";
import { DatePickerInitializer } from "./adapters/UI/fields/date-field/DatePickerInitializer";
import { ReadonlyDateFieldFormatter } from "./adapters/UI/fields/date-readonly-field/readonly-date-field-formatter";
import { FileUploadQuotaController } from "./domain/common/FileUploadQuotaController";
import { FileFieldController } from "./domain/fields/file-field/FileFieldController";
import { FaultFeedbackController } from "./domain/common/FaultFeedbackController";
import { PermissionFieldController } from "./adapters/UI/fields/permission-field/PermissionFieldController";
import { CheckboxFieldController } from "./adapters/UI/fields/checkbox-field/CheckboxFieldController";
import { FileFieldsUploader } from "./domain/fields/file-field/FileFieldsUploader";
import { FileUploader } from "./adapters/REST/fields/file-field/FileUploader";
import { getConfirmClosingModal, getSubmitDisabledReason } from "./gettext-catalog";
import { CommentsController } from "./domain/comments/CommentsController";
import { SelectBoxFieldController } from "./adapters/UI/fields/select-box-field/SelectBoxFieldController";
import { FieldDependenciesValuesHelper } from "./domain/fields/select-box-field/FieldDependenciesValuesHelper";
import { FormattedTextController } from "./domain/common/FormattedTextController";
import {
    EventDispatcher,
    WillDisableSubmit,
    WillEnableSubmit,
    WillNotifyFault,
} from "./domain/AllEvents";
import { StaticOpenListFieldController } from "./adapters/UI/fields/open-list-field/static/StaticOpenListFieldController";
import { UserGroupOpenListFieldController } from "./adapters/UI/fields/open-list-field/user-groups/UserGroupOpenListFieldController";
import { FormattedTextUserPreferences } from "./domain/common/FormattedTextUserPreferences";

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
    TuleapArtifactModalLoading,
) {
    const self = this;
    let confirm_action_to_edit = false;
    const concurrency_error_code = 412;
    let has_changed_once = false;

    const current_project_identifier = CurrentProjectIdentifier.fromId(
        modal_model.tracker.project.id,
    );
    const current_artifact_option = Option.fromNullable(modal_model.current_artifact_identifier);
    const current_tracker_identifier = CurrentTrackerIdentifier.fromId(modal_model.tracker_id);
    const parent_artifact_identifier = Option.fromNullable(modal_model.parent_artifact_id).map(
        ParentArtifactIdentifier.fromId,
    );
    const parent_tracker_identifier = Option.fromNullable(modal_model.tracker.parent).map(
        (parent_tracker) => ParentTrackerIdentifier.fromId(parent_tracker.id),
    );
    const user_locale = document.body.getAttribute("data-user-locale") ?? en_US_LOCALE;

    const event_dispatcher = EventDispatcher();
    const fault_feedback_controller = FaultFeedbackController(event_dispatcher);
    const api_client = TuleapAPIClient(current_project_identifier);
    const file_uploader = FileFieldsUploader(api_client, FileUploader());
    const links_store = LinksStore();
    const links_marked_for_removal_store = LinksMarkedForRemovalStore();
    const new_links_store = NewLinksStore();
    const link_field_creator = LinkFieldCreator(
        event_dispatcher,
        links_store,
        new_links_store,
        links_marked_for_removal_store,
        current_artifact_option,
        ArtifactCrossReference.fromCurrentArtifact(
            current_artifact_option,
            TrackerShortname.fromString(modal_model.tracker.item_name),
            modal_model.tracker.color_name,
        ),
        current_project_identifier,
        current_tracker_identifier,
        parent_artifact_identifier,
        parent_tracker_identifier,
        UserIdentifier.fromId(modal_model.user_id),
        user_locale,
    );

    Object.assign(self, {
        $onInit: init,
        current_artifact_identifier: current_artifact_option.unwrapOr(null), // Fields using it are not allowed in creation mode
        color: formatColor(modal_model.color),
        creation_mode: isInCreationMode(),
        ordered_fields: modal_model.ordered_fields,
        parent_artifact_id: modal_model.parent_artifact_id,
        title: getTitle(),
        tracker: modal_model.tracker,
        values: modal_model.values,
        submit_disabling_reason: Option.nothing(),
        did_artifact_links_change: false,
        new_followup_comment: {
            body: "",
            format: modal_model.text_fields_format,
        },
        link_field_value_formatter: LinkFieldValueFormatter(
            links_store,
            links_marked_for_removal_store,
            new_links_store,
        ),
        date_picker_initializer: DatePickerInitializer(),
        readonly_date_field_formatter: ReadonlyDateFieldFormatter(user_locale),
        parent_feedback_controller: ParentFeedbackController(
            api_client,
            event_dispatcher,
            parent_artifact_identifier,
        ),
        fault_feedback_controller,
        file_upload_quota_controller: FileUploadQuotaController(event_dispatcher),
        getCommentsController: () => {
            return CommentsController(
                api_client,
                event_dispatcher,
                current_artifact_option.unwrapOr(null), // It is not built in creation mode
                {
                    locale: modal_model.user_locale,
                    date_time_format: modal_model.user_date_time_format,
                    relative_dates_display: modal_model.relative_dates_display,
                    is_comment_order_inverted: modal_model.invert_followups_comments_order,
                    is_allowed_to_add_comment: isNotAnonymousUser(),
                    are_mentions_effective: modal_model.are_mentions_effective,
                    text_format: modal_model.text_fields_format,
                },
            );
        },
        getLinkFieldController(field) {
            return link_field_creator.createLinkFieldController(field, field.allowed_types);
        },
        getLinkFieldAutoCompleter() {
            return link_field_creator.createLinkSelectorAutoCompleter();
        },
        getArtifactCreatorController() {
            return link_field_creator.createArtifactCreatorController();
        },
        getFileFieldController: (field) => {
            return FileFieldController(field, self.values[field.field_id], event_dispatcher);
        },
        getPermissionFieldController: (field) => {
            return PermissionFieldController(
                field,
                self.values[field.field_id],
                self.isDisabled(field),
            );
        },
        getCheckboxFieldController: (field) => {
            return CheckboxFieldController(
                field,
                self.values[field.field_id].bind_value_ids,
                self.isDisabled(field),
            );
        },
        getSelectBoxFieldController: (field) => {
            return SelectBoxFieldController(
                event_dispatcher,
                field,
                self.values[field.field_id],
                self.isDisabled(field),
                user_locale,
            );
        },
        getFormattedTextController: () => {
            return FormattedTextController(
                event_dispatcher,
                api_client,
                FormattedTextUserPreferences.build(modal_model.text_fields_format, user_locale),
            );
        },
        getStaticOpenListFieldController: (field) => {
            const bind_value_objects = self.values[field.field_id].value.bind_value_objects;
            return StaticOpenListFieldController(field, bind_value_objects);
        },
        getUserGroupOpenListFieldController: (field) => {
            const bind_value_objects = self.values[field.field_id].value.bind_value_objects;
            return UserGroupOpenListFieldController(field, bind_value_objects);
        },
        hidden_fieldsets: extractHiddenFieldsets(modal_model.ordered_fields),
        formatColor,
        getRestErrorMessage: getErrorMessage,
        hasRestError: hasError,
        isDisabled,
        isSubmitDisabled: () => self.submit_disabling_reason.isValue(),
        onFormChange,
        setupTooltips,
        submit,
        reopenFieldsetsWithInvalidInput,
        setFieldValueForCustomElement,
        setFieldValueForRadioButtonsCustomElement,
        setFieldValueForComputedFieldElement,
        setFollowupComment,
        toggleFieldset,
        hasHiddenFieldsets,
        showHiddenFieldsets,
        confirm_action_to_edit,
        getButtonText,
        linkFieldChanged,
    });

    function getButtonText() {
        if (self.confirm_action_to_edit) {
            return gettextCatalog.getString("Confirm to apply your modifications");
        }

        return gettextCatalog.getString("Save changes");
    }

    function init() {
        event_dispatcher.addObserver("WillDisableSubmit", (event) => {
            // Wrap into $q so that AngularJS notices something happened
            $q.when(event.reason).then((reason) => {
                self.submit_disabling_reason = Option.fromValue(reason);
            });
        });
        event_dispatcher.addObserver("WillEnableSubmit", () => {
            // Wrap into $q so that AngularJS notices something happened
            $q.when().then(() => {
                self.submit_disabling_reason = Option.nothing();
            });
        });
        FieldDependenciesValuesHelper(event_dispatcher, self.tracker.workflow.rules.lists);

        TuleapArtifactModalLoading.loading = false;
        self.setupTooltips();
    }

    function setupTooltips() {
        $timeout(function () {
            loadTooltips();
        }, 0);
    }

    function getTitle() {
        return modal_model.title ?? "";
    }

    function isNotAnonymousUser() {
        return String(modal_model.user_id) !== "0";
    }

    function onFormChange() {
        if (has_changed_once) {
            return;
        }
        modal_instance.tlp_modal.addEventListener(EVENT_TLP_MODAL_WILL_HIDE, (event) => {
            event.preventDefault();
            // eslint-disable-next-line no-alert
            const should_close = window.confirm(getConfirmClosingModal());
            if (should_close) {
                modal_instance.tlp_modal.hide();
            }
        });
        has_changed_once = true;
    }

    function reopenFieldsetsWithInvalidInput(form) {
        const closed_fieldsets_that_contain_invalid_elements = form.querySelectorAll(
            "fieldset.tlp-pane-collapsed:invalid > div > legend",
        );
        for (const fieldset of closed_fieldsets_that_contain_invalid_elements) {
            if (fieldset instanceof HTMLElement) {
                fieldset.click();
            }
        }
    }

    function submit() {
        if (self.isSubmitDisabled() || TuleapArtifactModalLoading.loading) {
            return Promise.resolve(undefined);
        }
        event_dispatcher.dispatch(WillDisableSubmit(getSubmitDisabledReason()));
        TuleapArtifactModalLoading.loading = true;
        let is_error_already_handled = false;

        return file_uploader
            .uploadAllFileFields(getAllFileFields(Object.values(self.values)))
            .match(
                () => Promise.resolve(undefined),
                (fault) => {
                    event_dispatcher.dispatch(WillNotifyFault(fault));
                    is_error_already_handled = true;
                    return Promise.reject();
                },
            )
            .then(() => {
                const validated_values = validateArtifactFieldsValues(
                    self.values,
                    isInCreationMode(),
                    self.new_followup_comment,
                    self.link_field_value_formatter,
                );

                if (isInCreationMode()) {
                    return api_client
                        .createArtifact(current_tracker_identifier, validated_values)
                        .match(
                            (new_artifact) => Promise.resolve(new_artifact),
                            (fault) => {
                                event_dispatcher.dispatch(WillNotifyFault(fault));
                                is_error_already_handled = true;
                                return Promise.reject();
                            },
                        );
                }
                if (self.confirm_action_to_edit) {
                    return editArtifact(
                        modal_model.current_artifact_identifier.id,
                        validated_values,
                        self.new_followup_comment,
                    );
                }
                return editArtifactWithConcurrencyChecking(
                    modal_model.current_artifact_identifier.id,
                    validated_values,
                    self.new_followup_comment,
                    modal_model.etag,
                    modal_model.last_modified,
                );
            })
            .then(function (new_artifact) {
                modal_instance.tlp_modal.hide();
                return displayItemCallback(new_artifact.id, {
                    did_artifact_links_change: self.did_artifact_links_change,
                });
            })
            .catch(async (e) => {
                if (is_error_already_handled || hasError()) {
                    return;
                }
                await errorHandler(e);
            })
            .finally(function () {
                // Wrap into $q so that AngularJS notices something happened
                $q.when().then(() => {
                    TuleapArtifactModalLoading.loading = false;
                    event_dispatcher.dispatch(WillEnableSubmit());
                });
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
                        "Someone updated this artifact while you were editing it. Please note that your modifications will be applied on top of previous changes. You need to confirm your action to submit your modification.",
                    ),
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

    function toggleFieldset(fieldset) {
        fieldset.collapsed = !fieldset.collapsed;
    }

    function formatColor(color) {
        return color.split("_").join("-");
    }

    function linkFieldChanged() {
        self.did_artifact_links_change = true;
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

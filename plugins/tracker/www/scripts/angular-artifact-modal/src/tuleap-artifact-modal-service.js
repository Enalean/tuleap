import "./tuleap-artifact-modal.tpl.html";
import TuleapArtifactModalController from "./tuleap-artifact-modal-controller.js";

import _ from "lodash";
import { setCreationMode, isInCreationMode } from "./modal-creation-mode-state.js";
import { getArtifactFieldValues, getTracker, getUserPreference } from "./rest/rest-service.js";
import { updateFileUploadRulesWhenNeeded } from "./tuleap-artifact-modal-fields/file-field/file-upload-rules-state.js";

export default ArtifactModalService;

ArtifactModalService.$inject = [
    "$q",
    "TlpModalService",
    "TuleapArtifactModalLoading",
    "TuleapArtifactModalTrackerTransformerService",
    "TuleapArtifactModalFormTreeBuilderService",
    "TuleapArtifactFieldValuesService",
    "TuleapArtifactModalWorkflowService",
    "TuleapArtifactModalFieldDependenciesService"
];

function ArtifactModalService(
    $q,
    TlpModalService,
    TuleapArtifactModalLoading,
    TuleapArtifactModalTrackerTransformerService,
    TuleapArtifactModalFormTreeBuilderService,
    TuleapArtifactFieldValuesService,
    TuleapArtifactModalWorkflowService,
    TuleapArtifactModalFieldDependenciesService
) {
    const self = this;
    Object.assign(self, {
        initCreationModalModel,
        initEditionModalModel,
        showCreation,
        showEdition,
        loading: TuleapArtifactModalLoading
    });

    /**
     * Opens a new modal pop-in which will display a form with all the fields defined in the
     * given tracker.
     * displayItemCallback will be called after the last HTTP response is received.
     *
     * @param {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param {int} parent_artifact_id       The artifact's parent's id
     * @param {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the new artifact's id.
     * @param {array} prefill_values         The prefill values for creation, using field name as identifier
     */
    function showCreation(tracker_id, parent_artifact_id, displayItemCallback, prefill_values) {
        TuleapArtifactModalLoading.loading = true;

        return TlpModalService.open({
            templateUrl: "tuleap-artifact-modal.tpl.html",
            controller: TuleapArtifactModalController,
            controllerAs: "modal",
            tlpModalOptions: { keyboard: false },
            resolve: {
                modal_model: self.initCreationModalModel(
                    tracker_id,
                    parent_artifact_id,
                    prefill_values
                ),
                displayItemCallback: displayItemCallback ? displayItemCallback : _.noop
            }
        });
    }

    /**
     * Opens a new modal pop-in in edition mode, which will display a form with
     * all fields defined in the given tracker filled with the artifact's
     * existing values.
     * displayItemCallback will be called after the last HTTP response is received.
     *
     * @param {int} user_id                  The idea of current user
     * @param {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param {int} artifact_id              The id of the artifact we want to edit
     * @param {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the edited artifact's id.
     */
    function showEdition(user_id, tracker_id, artifact_id, displayItemCallback) {
        TuleapArtifactModalLoading.loading = true;

        return TlpModalService.open({
            templateUrl: "tuleap-artifact-modal.tpl.html",
            controller: TuleapArtifactModalController,
            controllerAs: "modal",
            tlpModalOptions: { keyboard: false },
            resolve: {
                modal_model: self.initEditionModalModel(user_id, tracker_id, artifact_id),
                displayItemCallback: displayItemCallback ? displayItemCallback : _.noop
            }
        });
    }

    var TEXT_FORMAT_TEXT_ID = "text";
    var TEXT_FORMAT_HTML_ID = "html";
    var text_formats = [
        { id: TEXT_FORMAT_TEXT_ID, label: "Text" },
        { id: TEXT_FORMAT_HTML_ID, label: "HTML" }
    ];

    function initCreationModalModel(tracker_id, parent_artifact_id, prefill_values) {
        var modal_model = {};

        const creation_mode = true;
        setCreationMode(creation_mode);
        modal_model.tracker_id = tracker_id;
        modal_model.parent_artifact_id = parent_artifact_id;
        modal_model.text_formats = text_formats;

        var promise = $q
            .when(getTracker(tracker_id))
            .then(function(tracker) {
                var transformed_tracker = TuleapArtifactModalTrackerTransformerService.transform(
                    tracker,
                    creation_mode
                );
                modal_model.tracker = transformed_tracker;
                modal_model.color = transformed_tracker.color_name;
                modal_model.title = transformed_tracker.label;

                var initial_values = mapPrefillsToFieldValues(
                    prefill_values || [],
                    modal_model.tracker.fields
                );
                applyWorkflowTransitions(transformed_tracker, {});
                modal_model.values = TuleapArtifactFieldValuesService.getSelectedValues(
                    initial_values,
                    transformed_tracker
                );
                applyFieldDependencies(transformed_tracker, modal_model.values);
                modal_model.ordered_fields = TuleapArtifactModalFormTreeBuilderService.buildFormTree(
                    transformed_tracker
                );

                const file_upload_rules_promise = $q.when(
                    updateFileUploadRulesWhenNeeded(transformed_tracker.fields)
                );
                return file_upload_rules_promise;
            })
            .then(function() {
                return modal_model;
            });

        return promise;
    }

    function initEditionModalModel(user_id, tracker_id, artifact_id) {
        var modal_model = {};

        const creation_mode = false;
        setCreationMode(creation_mode);
        modal_model.user_id = user_id;
        modal_model.tracker_id = tracker_id;
        modal_model.artifact_id = artifact_id;
        modal_model.text_formats = text_formats;
        var transformed_tracker;

        var promise = $q
            .all([
                getTracker(tracker_id),
                getArtifactValues(artifact_id),
                getFollowupsCommentsOrderUserPreference(user_id, tracker_id, modal_model),
                getTextFieldsFormatUserPreference(user_id, modal_model)
            ])
            .then(function(promises) {
                const tracker = promises[0];
                transformed_tracker = TuleapArtifactModalTrackerTransformerService.transform(
                    tracker,
                    creation_mode
                );

                modal_model.ordered_fields = transformed_tracker.ordered_fields;
                modal_model.color = transformed_tracker.color_name;

                const artifact_values = promises[1];
                let tracker_with_field_values = TuleapArtifactModalTrackerTransformerService.addFieldValuesToTracker(
                    artifact_values,
                    transformed_tracker
                );

                applyWorkflowTransitions(tracker_with_field_values, artifact_values);
                modal_model.values = TuleapArtifactFieldValuesService.getSelectedValues(
                    artifact_values,
                    transformed_tracker
                );
                modal_model.title = artifact_values.title;
                applyFieldDependencies(tracker_with_field_values, modal_model.values);

                modal_model.tracker = tracker_with_field_values;
                modal_model.ordered_fields = TuleapArtifactModalFormTreeBuilderService.buildFormTree(
                    tracker_with_field_values
                );

                const file_upload_rules_promise = $q.when(
                    updateFileUploadRulesWhenNeeded(transformed_tracker.fields)
                );
                return file_upload_rules_promise;
            })
            .then(function() {
                return modal_model;
            });

        return promise;
    }

    function getFollowupsCommentsOrderUserPreference(user_id, tracker_id, modal_model) {
        var preference_key = "tracker_comment_invertorder_" + tracker_id;

        return $q.when(getUserPreference(user_id, preference_key)).then(function(data) {
            modal_model.invert_followups_comments_order = Boolean(data.value);
        });
    }

    function getTextFieldsFormatUserPreference(user_id, modal_model) {
        return $q
            .when(getUserPreference(user_id, "user_edition_default_format"))
            .then(function(data) {
                modal_model.text_fields_format =
                    data.value !== false ? data.value : TEXT_FORMAT_TEXT_ID;
            });
    }

    function getArtifactValues(artifact_id) {
        var promise;
        if (artifact_id) {
            promise = getArtifactFieldValues(artifact_id);
        }
        return $q.when(promise);
    }

    function applyWorkflowTransitions(tracker, field_values) {
        if (!hasWorkflowTransitions(tracker)) {
            return;
        }
        var workflow = getWorkflow(tracker);

        var workflow_field = _.find(tracker.fields, { field_id: workflow.field_id });
        if (!workflow_field) {
            return;
        }

        var source_value_id;
        if (isInCreationMode()) {
            source_value_id = null;
        } else {
            source_value_id = field_values[workflow.field_id].bind_value_ids[0];
        }
        TuleapArtifactModalWorkflowService.enforceWorkflowTransitions(
            source_value_id,
            workflow_field,
            workflow
        );
    }

    function hasWorkflowTransitions(tracker) {
        return (
            _.has(tracker, "workflow") &&
            _.has(tracker.workflow, "transitions") &&
            tracker.workflow.is_used === "1" &&
            tracker.workflow.field_id
        );
    }

    function getWorkflow(tracker) {
        return tracker.workflow;
    }

    function applyFieldDependencies(tracker, field_values) {
        var filterTargetFieldValues = function(
            source_field_id,
            target_field,
            field_dependencies_rules
        ) {
            var source_value_ids = [].concat(field_values[source_field_id].bind_value_ids);

            target_field.filtered_values = TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues(
                source_value_ids,
                target_field,
                field_dependencies_rules
            );
        };

        TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions(
            tracker,
            filterTargetFieldValues
        );
    }

    function mapPrefillsToFieldValues(prefill_values, tracker_fields) {
        var field_values = {};

        prefill_values.forEach(function(prefill) {
            var field = _.find(tracker_fields, { name: prefill.name });
            if (field) {
                field_values[field.field_id] = Object.assign({}, prefill, {
                    field_id: field.field_id
                });
            }
        });

        return field_values;
    }
}

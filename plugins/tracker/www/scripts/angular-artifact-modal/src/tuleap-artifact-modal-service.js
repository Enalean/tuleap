angular
    .module('tuleap.artifact-modal')
    .service('NewTuleapArtifactModalService', NewTuleapArtifactModalService)
    .value('TuleapArtifactModalLoading', {
        loading: false
    });

NewTuleapArtifactModalService.$inject = [
    '$q',
    '$modal',
    'TuleapArtifactModalRestService',
    'TuleapArtifactModalLoading',
    'TuleapArtifactModalParentService',
    'TuleapArtifactModalTrackerTransformerService',
    'TuleapArtifactModalFormTreeBuilderService',
    'TuleapArtifactFieldValuesService',
    'TuleapArtifactModalWorkflowService',
    'TuleapArtifactModalFieldDependenciesService',
    'TuleapArtifactModalFileUploadRules'
];

function NewTuleapArtifactModalService(
    $q,
    $modal,
    TuleapArtifactModalRestService,
    TuleapArtifactModalLoading,
    TuleapArtifactModalParentService,
    TuleapArtifactModalTrackerTransformerService,
    TuleapArtifactModalFormTreeBuilderService,
    TuleapArtifactFieldValuesService,
    TuleapArtifactModalWorkflowService,
    TuleapArtifactModalFieldDependenciesService,
    TuleapArtifactModalFileUploadRules
) {
    var self = this;

    _.extend(self, {
        initCreationModalModel: initCreationModalModel,
        initEditionModalModel : initEditionModalModel,
        loading               : TuleapArtifactModalLoading,
        showCreation          : showCreation,
        showEdition           : showEdition
    });

    /**
     * Opens a new modal pop-in which will display a form with all the fields defined in the
     * given tracker.
     * displayItemCallback will be called after the last HTTP response is received.
     *
     * @param {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param {object} parent                The artifact's parent item
     * @param {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the new artifact's id.
     */
    function showCreation(tracker_id, parent, displayItemCallback) {
        TuleapArtifactModalLoading.loading = true;

        return $modal.open({
            backdrop   : 'static',
            keyboard   : false,
            templateUrl: 'tuleap-artifact-modal.tpl.html',
            controller : 'TuleapArtifactModalController as modal',
            resolve    : {
                modal_model: function() {
                    return self.initCreationModalModel(tracker_id, parent);
                },
                displayItemCallback: function() {
                    var cb = (displayItemCallback) ? displayItemCallback : angular.noop;
                    return cb;
                }
            },
            windowClass: 'tuleap-artifact-modal creation-mode'
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

        return $modal.open({
            backdrop   : 'static',
            keyboard   : false,
            templateUrl: 'tuleap-artifact-modal.tpl.html',
            controller : 'TuleapArtifactModalController as modal',
            resolve    : {
                modal_model: function() {
                    return self.initEditionModalModel(user_id, tracker_id, artifact_id);
                },
                displayItemCallback: function() {
                    var cb = (displayItemCallback) ? displayItemCallback : angular.noop;
                    return cb;
                }
            },
            windowClass: 'tuleap-artifact-modal edition-mode'
        });
    }

    var TEXT_FORMAT_TEXT_ID = 'text';
    var TEXT_FORMAT_HTML_ID = 'html';
    var text_formats = [
        { id: TEXT_FORMAT_TEXT_ID, label: 'Text' },
        { id: TEXT_FORMAT_HTML_ID, label: 'HTML' }
    ];

    function initCreationModalModel(tracker_id, parent_artifact) {
        var modal_model = {};

        modal_model.creation_mode = true;
        modal_model.tracker_id    = tracker_id;
        modal_model.parent        = parent_artifact;
        modal_model.text_formats  = text_formats;

        var promise = TuleapArtifactModalRestService.getTracker(tracker_id).then(function(tracker) {
            var transformed_tracker = TuleapArtifactModalTrackerTransformerService.transform(tracker, modal_model.creation_mode);
            var parent_tracker      = transformed_tracker.parent;
            modal_model.tracker     = transformed_tracker;
            modal_model.color       = transformed_tracker.color_name;
            modal_model.title       = transformed_tracker.label;

            applyWorkflowTransitions(transformed_tracker, modal_model.creation_mode, {});
            modal_model.values = TuleapArtifactFieldValuesService.getSelectedValues({}, transformed_tracker);
            applyFieldDependencies(transformed_tracker, modal_model.values);
            modal_model.ordered_fields = TuleapArtifactModalFormTreeBuilderService.buildFormTree(transformed_tracker);

            var parent_titles_promise     = loadParentArtifactsTitle(tracker_id, parent_tracker, parent_artifact, modal_model);
            var file_upload_rules_promise = updateFileUploadRules();

            return $q.all([parent_titles_promise, file_upload_rules_promise]);
        }).then(function() {
            return modal_model;
        });

        return promise;
    }

    function initEditionModalModel(user_id, tracker_id, artifact_id) {
        var modal_model = {};

        modal_model.creation_mode = false;
        modal_model.user_id       = user_id;
        modal_model.tracker_id    = tracker_id;
        modal_model.artifact_id   = artifact_id;
        modal_model.text_formats  = text_formats;
        var transformed_tracker;

        var promise = TuleapArtifactModalRestService.getTracker(tracker_id).then(function(tracker) {
            transformed_tracker        = TuleapArtifactModalTrackerTransformerService.transform(tracker, modal_model.creation_mode);
            modal_model.ordered_fields = transformed_tracker.ordered_fields;
            modal_model.color          = transformed_tracker.color_name;

            var get_values_promise                = getArtifactValues(artifact_id),
                comment_order_preference_promise  = getFollowupsCommentsOrderUserPreference(user_id, tracker_id, modal_model),
                comment_format_preference_promise = getTextFieldsFormatUserPreference(user_id, modal_model),
                file_upload_rules_promise         = updateFileUploadRules();

            return $q.all([
                get_values_promise,
                comment_order_preference_promise,
                comment_format_preference_promise,
                file_upload_rules_promise
            ]);
        }).then(function(promises) {
            var artifact_values = promises[0];
            var tracker_with_field_values = TuleapArtifactModalTrackerTransformerService.addFieldValuesToTracker(artifact_values, transformed_tracker);

            applyWorkflowTransitions(tracker_with_field_values, modal_model.creation_mode, artifact_values);
            modal_model.values = TuleapArtifactFieldValuesService.getSelectedValues(artifact_values, transformed_tracker);
            modal_model.title  = artifact_values.title;
            applyFieldDependencies(tracker_with_field_values, modal_model.values);

            modal_model.tracker        = tracker_with_field_values;
            modal_model.ordered_fields = TuleapArtifactModalFormTreeBuilderService.buildFormTree(tracker_with_field_values);

            return modal_model;
        });

        return promise;
    }

    function getFollowupsCommentsOrderUserPreference(user_id, tracker_id, modal_model) {
        var preference_key = 'tracker_comment_invertorder_' + tracker_id;

        return TuleapArtifactModalRestService.getUserPreference(user_id, preference_key)
            .then(function(data) {
                modal_model.invert_followups_comments_order = Boolean(data.value);
            });
    }

    function getTextFieldsFormatUserPreference(user_id, modal_model) {
        return TuleapArtifactModalRestService.getUserPreference(user_id, 'user_edition_default_format')
            .then(function(data) {
                modal_model.text_fields_format = (data.value !== false) ? data.value : TEXT_FORMAT_TEXT_ID;
            });
    }

    function loadParentArtifactsTitle(tracker_id, parent_tracker, parent_artifact, modal_model) {
        var promise;

        if (TuleapArtifactModalParentService.canChooseArtifactsParent(parent_tracker, parent_artifact)) {
            promise = TuleapArtifactModalRestService.getAllOpenParentArtifacts(tracker_id, 1000, 0)
                .then(function(artifacts_data) {
                    modal_model.parent_artifacts = artifacts_data;
                });
        }

        return $q.when(promise);
    }

    function getArtifactValues(artifact_id) {
        var promise;
        if (artifact_id) {
            promise = TuleapArtifactModalRestService.getArtifactFieldValues(artifact_id);
        }
        return $q.when(promise);
    }

    function applyWorkflowTransitions(tracker, creation_mode, field_values) {
        if (! hasWorkflowTransitions(tracker)) {
            return;
        }
        var workflow = getWorkflow(tracker);

        var workflow_field = _.find(tracker.fields, { field_id: workflow.field_id });
        if (! workflow_field) {
            return;
        }

        var source_value_id;
        if (creation_mode) {
            source_value_id = null;
        } else {
            source_value_id = field_values[workflow.field_id].bind_value_ids[0];
        }
        TuleapArtifactModalWorkflowService.enforceWorkflowTransitions(source_value_id, workflow_field, workflow);
    }

    function hasWorkflowTransitions(tracker) {
        return (
            _.has(tracker, 'workflow') &&
            _.has(tracker.workflow, 'transitions') &&
            (tracker.workflow.is_used === '1') &&
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

        TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions(tracker, filterTargetFieldValues);
    }

    function updateFileUploadRules() {
        var promise = TuleapArtifactModalRestService.getFileUploadRules()
            .then(function(data) {
                TuleapArtifactModalFileUploadRules.disk_quota     = data.disk_quota;
                TuleapArtifactModalFileUploadRules.disk_usage     = data.disk_usage;
                TuleapArtifactModalFileUploadRules.max_chunk_size = data.max_chunk_size;
            });

        return promise;
    }
}

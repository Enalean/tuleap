import artifact_modal_module from './tuleap-artifact-modal.js';
import angular               from 'angular';
import 'angular-mocks';

import {
    rewire$setCreationMode,
    rewire$isInCreationMode,
    restore as restoreCreationMode
} from './modal-creation-mode-state.js';

describe("NewTuleapArtifactModalService", () => {
    let NewTuleapArtifactModalService,
        $q,
        TuleapArtifactModalRestService,
        TuleapArtifactFieldValuesService,
        TuleapArtifactModalTrackerTransformerService,
        TuleapArtifactModalFormTreeBuilderService,
        TuleapArtifactModalWorkflowService,
        setCreationMode,
        isInCreationMode;

    beforeEach(() => {
        angular.mock.module(artifact_modal_module, function($provide) {
            $provide.decorator('TuleapArtifactModalRestService', function($delegate) {
                spyOn($delegate, "getArtifactFieldValues");
                spyOn($delegate, "getTracker");
                spyOn($delegate, "getUserPreference");
                spyOn($delegate, "getFileUploadRules");

                return $delegate;
            });

            $provide.decorator('TuleapArtifactModalTrackerTransformerService', function($delegate) {
                spyOn($delegate, "addFieldValuesToTracker").and.callFake(function(artifact_values, tracker) {
                    return tracker;
                });
                spyOn($delegate, "transform").and.callFake(function(tracker) {
                    return tracker;
                });

                return $delegate;
            });

            $provide.decorator('TuleapArtifactFieldValuesService', function($delegate) {
                spyOn($delegate, "getSelectedValues").and.callFake(function() {
                    return {};
                });

                return $delegate;
            });

            $provide.decorator('TuleapArtifactModalFormTreeBuilderService', function($delegate) {
                spyOn($delegate, "buildFormTree").and.callFake(function() {
                    return {};
                });

                return $delegate;
            });

            $provide.decorator('TuleapArtifactModalWorkflowService', function($delegate) {
                spyOn($delegate, "enforceWorkflowTransitions");

                return $delegate;
            });
        });

        angular.mock.inject(function(
            _$q_,
            _TuleapArtifactModalRestService_,
            _TuleapArtifactModalTrackerTransformerService_,
            _TuleapArtifactFieldValuesService_,
            _TuleapArtifactModalFormTreeBuilderService_,
            _TuleapArtifactModalWorkflowService_,
            _NewTuleapArtifactModalService_
        ) {
            $q                                           = _$q_;
            TuleapArtifactModalRestService               = _TuleapArtifactModalRestService_;
            TuleapArtifactModalTrackerTransformerService = _TuleapArtifactModalTrackerTransformerService_;
            TuleapArtifactFieldValuesService             = _TuleapArtifactFieldValuesService_;
            TuleapArtifactModalFormTreeBuilderService    = _TuleapArtifactModalFormTreeBuilderService_;
            TuleapArtifactModalWorkflowService           = _TuleapArtifactModalWorkflowService_;
            NewTuleapArtifactModalService                = _NewTuleapArtifactModalService_;
        });

        installPromiseMatchers();
        setCreationMode = jasmine.createSpy("setCreationMode");
        rewire$setCreationMode(setCreationMode);
        isInCreationMode = jasmine.createSpy("isInCreationMode");
        rewire$isInCreationMode(isInCreationMode);
    });

    afterEach(() => {
        restoreCreationMode();
    });

    describe("", () => {
        let tracker,
            file_upload_rules;

        beforeEach(() => {
            file_upload_rules = {
                disk_quota    : 64,
                disk_usage    : 57,
                max_chunk_size: 96
            };
        });

        describe("initCreationModalModel() -", () => {
            let tracker_id, parent_artifact_id;

            beforeEach(() => {
                tracker_id         = 28;
                parent_artifact_id = 581;
            });


            it("Given a tracker id and a parent artifact id, then the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", () => {
                tracker = {
                    id        : tracker_id,
                    color_name: "importer",
                    label     : "preinvest",
                    parent    : null
                };
                TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));
                TuleapArtifactModalRestService.getFileUploadRules.and.returnValue($q.when(file_upload_rules));

                const promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact_id);

                expect(promise).toBeResolved();
                expect(TuleapArtifactModalRestService.getTracker).toHaveBeenCalledWith(tracker_id);
                expect(TuleapArtifactModalRestService.getFileUploadRules).toHaveBeenCalled();
                expect(TuleapArtifactFieldValuesService.getSelectedValues).toHaveBeenCalledWith({}, tracker);
                expect(TuleapArtifactModalTrackerTransformerService.transform).toHaveBeenCalledWith(tracker, true);
                expect(TuleapArtifactModalFormTreeBuilderService.buildFormTree).toHaveBeenCalledWith(tracker);
                const model = promise.$$state.value;
                expect(setCreationMode).toHaveBeenCalledWith(true);
                expect(model.tracker_id).toEqual(tracker_id);
                expect(model.parent_artifact_id).toEqual(parent_artifact_id);
                expect(model.tracker).toEqual(tracker);
                expect(model.title).toEqual("preinvest");
                expect(model.color).toEqual("importer");
                expect(model.values).toBeDefined();
                expect(model.ordered_fields).toBeDefined();
                expect(model.parent_artifacts).toBeUndefined();
                expect(model.artifact_id).toBeUndefined();
                expect(model.text_formats).toEqual([
                    { id: 'text', label: 'Text' },
                    { id: 'html', label: 'HTML' }
                ]);
            });

            it("Given that I could not get the tracker structure, then a promise will be rejected", () => {
                TuleapArtifactModalRestService.getTracker.and.returnValue($q.reject());

                const promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact_id);

                expect(promise).toBeRejected();
            });

            describe("apply transitions -", () => {
                beforeEach(() => {
                    isInCreationMode.and.returnValue(true);
                });

                it("Given a tracker that had workflow transitions, when I create the modal's creation model, then the transitions will be enforced", () => {
                    const workflow_field = {
                        field_id: 189
                    };
                    const workflow = {
                        is_used: "1",
                        field_id: 189,
                        transitions: [
                            {
                                from_id: null,
                                to_id: 511
                            }
                        ]
                    };
                    tracker = {
                        id: tracker_id,
                        fields: [
                            workflow_field
                        ],
                        workflow
                    };
                    TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));
                    TuleapArtifactModalRestService.getFileUploadRules.and.returnValue($q.when(file_upload_rules));

                    const promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).toHaveBeenCalledWith(null, workflow_field, workflow);
                });

                it("Given a tracker that had workflow transitions but were not used, then the transitions won't be enforced", () => {
                    const workflow_field = {
                        field_id: tracker_id
                    };
                    const workflow = {
                        is_used: "0",
                        field_id: 189,
                        transitions: [
                            {
                                from_id: 326,
                                to_id: 723
                            }
                        ]
                    };
                    tracker = {
                        id: tracker_id,
                        fields: [
                            workflow_field
                        ],
                        workflow
                    };
                    TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));
                    TuleapArtifactModalRestService.getFileUploadRules.and.returnValue($q.when(file_upload_rules));

                    const promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).not.toHaveBeenCalled();
                });

                it("Given a tracker that didn't have workflow transitions, when I create the modal's creation model, then the transitions won't be enforced", () => {
                    const workflow_field = {
                        field_id: tracker_id
                    };
                    const workflow = {
                        is_used: "1",
                        field_id: 189,
                        transitions: []
                    };
                    tracker = {
                        id: tracker_id,
                        fields: [
                            workflow_field
                        ],
                        workflow
                    };
                    TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));
                    TuleapArtifactModalRestService.getFileUploadRules.and.returnValue($q.when(file_upload_rules));

                    var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).not.toHaveBeenCalled();
                });
            });
        });

        describe("initEditionModalModel() -", function() {
            var user_id,
                tracker_id,
                artifact_id,
                artifact_values;

            beforeEach(function() {
                TuleapArtifactFieldValuesService.getSelectedValues.and.callFake(function() {
                    return {
                        113: {
                            value: "onomatomania"
                        }
                    };
                });

                var comment_order_preference = {
                    key  : 'tracker_comment_invertorder_93',
                    value: '1'
                };

                var text_format_preference = {
                    key  : 'user_edition_default_format',
                    value: 'html'
                };

                file_upload_rules = {
                    disk_quota    : 51,
                    disk_usage    : 17,
                    max_chunk_size: 57
                };

                user_id         = 102;
                tracker_id      = 93;
                artifact_id     = 250;
                TuleapArtifactModalRestService.getUserPreference.and.callFake(function(user_id, preference_key) {
                    if (preference_key.contains('tracker_comment_invertorder_')) {
                        return $q.when(comment_order_preference);
                    } else if (preference_key === 'user_edition_default_format') {
                        return $q.when(text_format_preference);
                    }
                });
                TuleapArtifactModalRestService.getFileUploadRules.and.returnValue($q.when(file_upload_rules));
            });

            describe("", function() {
                beforeEach(function() {
                    tracker = {
                        id        : tracker_id,
                        color_name: "slackerism",
                        label     : "unstainableness",
                        parent    : null
                    };
                    artifact_values = {
                        487: {
                            field_id: 487,
                            value   : "unwadded"
                        },
                        'title': 'onomatomania'
                    };
                    TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));
                    TuleapArtifactModalRestService.getArtifactFieldValues.and.returnValue($q.when(artifact_values));
                });

                it("Given a user id, tracker id and an artifact id, when I create the modal's edition model, then the artifact's field values will be retrieved, the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", function() {
                    var promise = NewTuleapArtifactModalService.initEditionModalModel(user_id, tracker_id, artifact_id);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalRestService.getTracker).toHaveBeenCalledWith(tracker_id);
                    expect(TuleapArtifactModalRestService.getArtifactFieldValues).toHaveBeenCalledWith(artifact_id);
                    expect(TuleapArtifactModalRestService.getUserPreference).toHaveBeenCalledWith(user_id, 'tracker_comment_invertorder_93');
                    expect(TuleapArtifactModalRestService.getFileUploadRules).toHaveBeenCalled();
                    expect(TuleapArtifactFieldValuesService.getSelectedValues).toHaveBeenCalledWith(artifact_values, tracker);
                    expect(TuleapArtifactModalTrackerTransformerService.transform).toHaveBeenCalledWith(tracker, false);
                    expect(TuleapArtifactModalTrackerTransformerService.addFieldValuesToTracker).toHaveBeenCalledWith(artifact_values, tracker);
                    expect(TuleapArtifactModalFormTreeBuilderService.buildFormTree).toHaveBeenCalledWith(tracker);
                    var model = promise.$$state.value;
                    expect(model.invert_followups_comments_order).toBeTruthy();
                    expect(model.text_fields_format).toEqual('html');
                    expect(model.tracker_id).toEqual(tracker_id);
                    expect(model.artifact_id).toEqual(artifact_id);
                    expect(model.color).toEqual("slackerism");
                    expect(model.tracker).toEqual(tracker);
                    expect(model.values).toBeDefined();
                    expect(model.ordered_fields).toBeDefined();
                    expect(setCreationMode).toHaveBeenCalledWith(false);
                    expect(model.title).toEqual("onomatomania");
                    expect(model.text_formats).toEqual([
                        { id: 'text', label: 'Text' },
                        { id: 'html', label: 'HTML' }
                    ]);
                });

                it("Given that the user didn't have a preference set for text fields format, when I create the modal's edition model, then the default text_field format will be 'text' by default", function() {
                    var comment_order_preference = {
                        key  : 'tracker_comment_invertorder_93',
                        value: '1'
                    };

                    TuleapArtifactModalRestService.getUserPreference.and.callFake(function(user_id, preference_key) {
                        if (preference_key.contains('tracker_comment_invertorder_')) {
                            return $q.when(comment_order_preference);
                        } else if (preference_key === 'user_edition_default_format') {
                            return $q.when({
                                key  : 'user_edition_default_format',
                                value: false
                            });
                        }
                    });

                    var promise = NewTuleapArtifactModalService.initEditionModalModel(user_id, tracker_id, artifact_id);

                    expect(promise).toBeResolved();
                    var model = promise.$$state.value;

                    expect(model.text_fields_format).toEqual('text');
                });
            });


            describe("apply transitions -", function() {
                var workflow_field;

                beforeEach(function() {
                    workflow_field = {
                        field_id: 189
                    };
                    artifact_values = {
                        189: {
                            field_id      : 189,
                            bind_value_ids: [757]
                        }
                    };
                    TuleapArtifactModalRestService.getArtifactFieldValues.and.returnValue($q.when(artifact_values));
                });

                it("Given a tracker that had workflow transitions, when I create the modal's edition model, then the transitions will be enforced", function() {
                    var workflow = {
                        is_used    : "1",
                        field_id   : 189,
                        transitions: [
                            {
                                from_id: 757,
                                to_id  : 511
                            }
                        ]
                    };
                    tracker = {
                        id    : tracker_id,
                        fields: [
                            workflow_field
                        ],
                        workflow: workflow
                    };
                    TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));

                    var promise = NewTuleapArtifactModalService.initEditionModalModel(user_id, tracker_id, artifact_id);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).toHaveBeenCalledWith(757, workflow_field, workflow);
                });

                it("Given a tracker that had workflow transitions but were not used, when I create the modal's edition model, then the transitions won't be enforced", function() {
                    var workflow = {
                        is_used    : "0",
                        field_id   : 189,
                        transitions: [
                            {
                                from_id: 757,
                                to_id  : 511
                            }
                        ]
                    };
                    tracker = {
                        id    : tracker_id,
                        fields: [
                            workflow_field
                        ],
                        workflow: workflow
                    };
                    TuleapArtifactModalRestService.getTracker.and.returnValue($q.when(tracker));

                    var promise = NewTuleapArtifactModalService.initEditionModalModel(user_id, tracker_id, artifact_id);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).not.toHaveBeenCalled();
                });
            });
        });
    });
});

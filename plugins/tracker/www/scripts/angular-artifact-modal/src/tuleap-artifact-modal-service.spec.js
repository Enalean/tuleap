describe("NewTuleapArtifactModalService", function() {
    var NewTuleapArtifactModalService, $modal, $rootScope, $q, TuleapArtifactModalRestService,
        TuleapArtifactFieldValuesService, TuleapArtifactModalParentService,
        TuleapArtifactModalTrackerTransformerService, TuleapArtifactModalFormTreeBuilderService,
        TuleapArtifactModalWorkflowService;

    beforeEach(function() {
        module('tuleap.artifact-modal', function($provide) {
            $provide.decorator('TuleapArtifactModalRestService', function($delegate) {
                spyOn($delegate, "getArtifactFieldValues");
                spyOn($delegate, "getAllOpenParentArtifacts");
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

            $provide.decorator('TuleapArtifactModalParentService', function($delegate) {
                spyOn($delegate, "canChooseArtifactsParent");

                return $delegate;
            });

            $provide.decorator('TuleapArtifactModalWorkflowService', function($delegate) {
                spyOn($delegate, "enforceWorkflowTransitions");

                return $delegate;
            });
        });

        inject(function(
            _$modal_,
            _$q_,
            _TuleapArtifactModalRestService_,
            _TuleapArtifactModalTrackerTransformerService_,
            _TuleapArtifactFieldValuesService_,
            _TuleapArtifactModalFormTreeBuilderService_,
            _TuleapArtifactModalParentService_,
            _TuleapArtifactModalWorkflowService_,
            _NewTuleapArtifactModalService_,
            _$rootScope_
        ) {
            $modal                                       = _$modal_;
            $q                                           = _$q_;
            TuleapArtifactModalRestService               = _TuleapArtifactModalRestService_;
            TuleapArtifactModalTrackerTransformerService = _TuleapArtifactModalTrackerTransformerService_;
            TuleapArtifactFieldValuesService             = _TuleapArtifactFieldValuesService_;
            TuleapArtifactModalFormTreeBuilderService    = _TuleapArtifactModalFormTreeBuilderService_;
            TuleapArtifactModalParentService             = _TuleapArtifactModalParentService_;
            TuleapArtifactModalWorkflowService           = _TuleapArtifactModalWorkflowService_;
            NewTuleapArtifactModalService                = _NewTuleapArtifactModalService_;
            $rootScope                                   = _$rootScope_;
        });

        installPromiseMatchers();
    });


    describe("", function() {
        var tracker_request, tracker, parent_artifacts_request, file_upload_rules_request,
            file_upload_rules;

        beforeEach(function() {
            tracker_request           = $q.defer();
            parent_artifacts_request  = $q.defer();
            file_upload_rules_request = $q.defer();
            TuleapArtifactModalRestService.getTracker.and.returnValue(tracker_request.promise);
            TuleapArtifactModalRestService.getAllOpenParentArtifacts.and.returnValue(parent_artifacts_request.promise);
            TuleapArtifactModalRestService.getFileUploadRules.and.returnValue(file_upload_rules_request.promise);

            file_upload_rules = {
                disk_quota    : 64,
                disk_usage    : 57,
                max_chunk_size: 96
            };
        });

        describe("initCreationModalModel() -", function() {
            var tracker_id, parent_artifact;

            beforeEach(function() {
                tracker_id      = 28;
                parent_artifact = { id: 581 };
            });


            it("Given a tracker id and a parent artifact, when I create the modal's creation model, then the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", function() {
                TuleapArtifactModalParentService.canChooseArtifactsParent.and.returnValue(false);
                tracker = {
                    id: tracker_id,
                    color_name: "importer",
                    label: "preinvest",
                    parent: null
                };

                var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact);
                tracker_request.resolve(tracker);
                file_upload_rules_request.resolve(file_upload_rules);

                expect(promise).toBeResolved();
                expect(TuleapArtifactModalRestService.getTracker).toHaveBeenCalledWith(tracker_id);
                expect(TuleapArtifactModalRestService.getAllOpenParentArtifacts).not.toHaveBeenCalled();
                expect(TuleapArtifactModalRestService.getFileUploadRules).toHaveBeenCalled();
                expect(TuleapArtifactFieldValuesService.getSelectedValues).toHaveBeenCalledWith({}, tracker);
                expect(TuleapArtifactModalTrackerTransformerService.transform).toHaveBeenCalledWith(tracker, true);
                expect(TuleapArtifactModalFormTreeBuilderService.buildFormTree).toHaveBeenCalledWith(tracker);
                var model = promise.$$state.value;
                expect(model.creation_mode).toBeTruthy();
                expect(model.tracker_id).toEqual(tracker_id);
                expect(model.parent).toEqual(parent_artifact);
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

            it("Given a tracker id and given I can choose a parent artifact, when I create the modal's creation model, then all the possible parent artifacts for this tracker will be retrieved and added to the model and a promise will be resolved with the modal's model object", function() {
                TuleapArtifactModalParentService.canChooseArtifactsParent.and.returnValue(true);
                tracker = {
                    id: tracker_id,
                    parent: {
                        id: 79
                    }
                };
                var possible_parents = [
                    { id: 933, title: "elastivity" },
                    { id: 194, title: "unalleviably" }
                ];
                parent_artifact = { id: 770 };

                var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact);
                tracker_request.resolve(tracker);
                parent_artifacts_request.resolve(possible_parents);
                file_upload_rules_request.resolve(file_upload_rules);

                expect(promise).toBeResolved();
                expect(TuleapArtifactModalRestService.getAllOpenParentArtifacts).toHaveBeenCalledWith(tracker_id, 1000, 0);
                var model = promise.$$state.value;
                expect(model.parent_artifacts).toEqual(possible_parents);
            });

            it("Given that I could not get the list of possible parents, when I create the modal's creation model, then a promise will be rejected", function() {
                TuleapArtifactModalParentService.canChooseArtifactsParent.and.returnValue(true);
                tracker = {
                    id: tracker_id,
                    parent: {
                        id: 14
                    }
                };

                var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact);
                tracker_request.resolve(tracker);
                file_upload_rules_request.resolve(file_upload_rules);
                parent_artifacts_request.reject();

                expect(promise).toBeRejected();
            });

            it("Given that I could not get the tracker structure, when I create the modal's creation model, then a promise will be rejected", function() {
                var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact);
                tracker_request.reject();

                expect(promise).toBeRejected();
            });

            describe("apply transitions -", function() {
                var workflow_field;

                beforeEach(function() {
                    workflow_field = {
                        field_id: 189
                    };
                });

                it("Given a tracker that had workflow transitions, when I create the modal's creation model, then the transitions will be enforced", function() {
                    var workflow = {
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
                        workflow: workflow
                    };

                    var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id);
                    tracker_request.resolve(tracker);
                    file_upload_rules_request.resolve(file_upload_rules);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).toHaveBeenCalledWith(null, workflow_field, workflow);
                });

                it("Given a tracker that had workflow transitions but were not used, when I create the modal's creation model, then the transitions won't be enforced", function() {
                    var workflow_field = {
                        field_id: tracker_id
                    };
                    var workflow = {
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
                        workflow: workflow
                    };

                    var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id);
                    tracker_request.resolve(tracker);
                    file_upload_rules_request.resolve(file_upload_rules);

                    expect(promise).toBeResolved();
                    expect(TuleapArtifactModalWorkflowService.enforceWorkflowTransitions).not.toHaveBeenCalled();
                });

                it("Given a tracker that didn't have workflow transitions, when I create the modal's creation model, then the transitions won't be enforced", function() {
                    var workflow_field = {
                        field_id: tracker_id
                    };
                    var workflow = {
                        is_used: "1",
                        field_id: 189,
                        transitions: []
                    };
                    tracker = {
                        id: tracker_id,
                        fields: [
                            workflow_field
                        ],
                        workflow: workflow
                    };

                    var promise = NewTuleapArtifactModalService.initCreationModalModel(tracker_id);
                    tracker_request.resolve(tracker);
                    file_upload_rules_request.resolve(file_upload_rules);

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
                    expect(model.creation_mode).toBeFalsy();
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

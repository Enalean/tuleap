import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

import BaseModalController from "./tuleap-artifact-modal-controller.js";

import {
    rewire$isInCreationMode,
    restore as restoreCreationMode
} from "./modal-creation-mode-state.js";
import {
    rewire$createArtifact,
    rewire$editArtifact,
    rewire$getFollowupsComments,
    restore as restoreRest
} from "./rest/rest-service.js";
import {
    rewire$getAllFileFields,
    restore as restoreFileDetector
} from "./tuleap-artifact-modal-fields/file-field/file-field-detector.js";
import {
    rewire$uploadAllTemporaryFiles,
    restore as restoreFileUpload
} from "./tuleap-artifact-modal-fields/file-field/file-uploader.js";

describe("TuleapArtifactModalController", () => {
    let $scope,
        $q,
        $controller,
        controller_params,
        ArtifactModalController,
        tlp_modal,
        TuleapArtifactModalValidateService,
        TuleapArtifactModalFieldDependenciesService,
        TuleapArtifactModalLoading,
        mockCallback,
        isInCreationMode,
        getFollowupsComments,
        createArtifact,
        editArtifact,
        getAllFileFields,
        uploadAllTemporaryFiles;

    beforeEach(() => {
        angular.mock.module(artifact_modal_module, function($provide) {
            $provide.decorator("TuleapArtifactModalValidateService", function($delegate) {
                spyOn($delegate, "validateArtifactFieldsValues");

                return $delegate;
            });

            $provide.decorator("TuleapArtifactModalFieldDependenciesService", function($delegate) {
                spyOn($delegate, "getTargetFieldPossibleValues");
                spyOn($delegate, "setUpFieldDependenciesActions");

                return $delegate;
            });
        });

        angular.mock.inject(function(
            _$controller_,
            $rootScope,
            _$q_,
            _$timeout_,
            _TuleapArtifactModalValidateService_,
            _TuleapArtifactModalFieldDependenciesService_,
            _TuleapArtifactModalLoading_
        ) {
            $q = _$q_;
            TuleapArtifactModalValidateService = _TuleapArtifactModalValidateService_;
            TuleapArtifactModalFieldDependenciesService = _TuleapArtifactModalFieldDependenciesService_;
            TuleapArtifactModalLoading = _TuleapArtifactModalLoading_;

            tlp_modal = jasmine.createSpyObj("tlp_modal", ["hide"]);
            const modal_instance = {
                tlp_modal: tlp_modal
            };

            mockCallback = jasmine.createSpy("displayItemCallback");
            $scope = $rootScope.$new();

            $controller = _$controller_;
            controller_params = {
                $scope: $scope,
                modal_instance: modal_instance,
                modal_model: {
                    title: {
                        content: ""
                    },
                    color: "inca_silver"
                },
                TuleapArtifactModalValidateService,
                TuleapArtifactModalLoading,
                TuleapArtifactModalFieldDependenciesService,
                displayItemCallback: mockCallback
            };
        });

        isInCreationMode = jasmine.createSpy("isInCreationMode");
        rewire$isInCreationMode(isInCreationMode);

        getFollowupsComments = jasmine.createSpy("getFollowupsComments").and.returnValue(
            $q.resolve({
                data: {
                    results: []
                },
                total: 0
            })
        );
        rewire$getFollowupsComments(getFollowupsComments);
        createArtifact = jasmine.createSpy("createArtifact");
        rewire$createArtifact(createArtifact);
        editArtifact = jasmine.createSpy("editArtifact");
        rewire$editArtifact(editArtifact);
        getAllFileFields = jasmine.createSpy("getAllFileFields");
        rewire$getAllFileFields(getAllFileFields);
        uploadAllTemporaryFiles = jasmine.createSpy("uploadAllTemporaryFiles");
        rewire$uploadAllTemporaryFiles(uploadAllTemporaryFiles);
    });

    afterEach(() => {
        restoreCreationMode();
        restoreRest();
        restoreFileDetector();
        restoreFileUpload();
    });

    describe("init() -", function() {
        describe("", function() {
            beforeEach(function() {
                spyOn($scope, "$watch");
                TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions.and.callThrough();
            });

            it("when I load the controller, then field dependencies watchers will be set once for each different source field", function() {
                controller_params.modal_model.tracker = {
                    fields: [{ field_id: 22 }],
                    workflow: {
                        rules: {
                            lists: [
                                {
                                    source_field_id: 43,
                                    source_value_id: 752,
                                    target_field_id: 22,
                                    target_value_id: 519
                                }
                            ]
                        }
                    }
                };

                ArtifactModalController = $controller(BaseModalController, controller_params);

                expect($scope.$watch).toHaveBeenCalled();
                expect($scope.$watch.calls.count()).toEqual(1);
                expect(
                    TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions
                ).toHaveBeenCalledWith(
                    controller_params.modal_model.tracker,
                    jasmine.any(Function)
                );
            });
        });
    });

    describe("submit() - Given a tracker id, field values, a callback function", () => {
        beforeEach(() => {
            TuleapArtifactModalValidateService.validateArtifactFieldsValues.and.callFake(
                values => values
            );
            getAllFileFields.and.returnValue([]);
        });

        it("and no artifact_id, when I submit the modal to Tuleap, then the field values will be validated, the artifact will be created , the modal will be closed and the callback will be called", () => {
            createArtifact.and.returnValue($q.resolve({ id: 3042 }));
            isInCreationMode.and.returnValue(true);
            controller_params.modal_model.tracker_id = 39;
            ArtifactModalController = $controller(BaseModalController, controller_params);
            var values = [{ field_id: 359, value: 907 }, { field_id: 613, bind_value_ids: [919] }];
            ArtifactModalController.values = values;

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            $scope.$apply();

            expect(
                TuleapArtifactModalValidateService.validateArtifactFieldsValues
            ).toHaveBeenCalledWith(values, true);
            expect(createArtifact).toHaveBeenCalledWith(39, values);
            expect(editArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
            expect(mockCallback).toHaveBeenCalled();
        });

        it("and an artifact_id to edit, when I submit the modal to Tuleap, then the field values will be validated, the artifact will be edited, the modal will be closed and the callback will be called", () => {
            var edit_request = $q.defer();
            editArtifact.and.returnValue(edit_request.promise);
            isInCreationMode.and.returnValue(false);
            controller_params.modal_model.artifact_id = 8155;
            controller_params.modal_model.tracker_id = 186;
            ArtifactModalController = $controller(BaseModalController, controller_params);
            var values = [{ field_id: 983, value: 741 }, { field_id: 860, bind_value_ids: [754] }];
            var followup_comment = {
                body: "My comment",
                format: "text"
            };
            ArtifactModalController.values = values;
            ArtifactModalController.followup_comment = followup_comment;

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            edit_request.resolve({ id: 8155 });
            $scope.$apply();

            expect(
                TuleapArtifactModalValidateService.validateArtifactFieldsValues
            ).toHaveBeenCalledWith(values, false);
            expect(editArtifact).toHaveBeenCalledWith(8155, values, followup_comment);
            expect(createArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
            expect(mockCallback).toHaveBeenCalledWith(8155);
        });

        it("and given that there were 2 file fields, when I submit the modal to Tuleap, then all temporary files chosen in those fields will be uploaded before fields are validated", () => {
            ArtifactModalController = $controller(BaseModalController, controller_params);
            var first_field_temporary_files = [{ description: "one" }];
            var second_field_temporary_files = [{ description: "two" }];
            var first_file_field_value = {
                field_id: 198,
                temporary_files: first_field_temporary_files,
                type: "file",
                value: [66]
            };
            var second_file_field_value = {
                field_id: 277,
                temporary_files: second_field_temporary_files,
                type: "file",
                value: []
            };
            var first_upload = $q.defer();
            var second_upload = $q.defer();
            uploadAllTemporaryFiles.and.callFake(temporary_files => {
                switch (temporary_files[0].description) {
                    case "one":
                        return first_upload.promise;
                    case "two":
                        return second_upload.promise;
                }
            });
            var edit_request = $q.defer();
            editArtifact.and.returnValue(edit_request.promise);
            var values = [first_file_field_value, second_file_field_value];
            ArtifactModalController.values = values;
            getAllFileFields.and.returnValue(values);

            ArtifactModalController.submit();
            first_upload.resolve([47]);
            second_upload.resolve([71, undefined]);
            edit_request.resolve({ id: 144 });
            $scope.$apply();

            expect(uploadAllTemporaryFiles).toHaveBeenCalledWith(first_field_temporary_files);
            expect(uploadAllTemporaryFiles).toHaveBeenCalledWith(second_field_temporary_files);
            expect(first_file_field_value.value).toEqual([66, 47]);
            expect(second_file_field_value.value).toEqual([71]);
        });

        it("and given the server responded an error, when I submit the modal to Tuleap, then the modal will not be closed and the callback won't be called", () => {
            editArtifact.and.returnValue($q.reject());
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.values = [];

            ArtifactModalController.submit();
            $scope.$apply();

            expect(tlp_modal.hide).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
        });
    });

    describe("Field dependency watchers - Given a field dependency rule was defined in the tracker", function() {
        beforeEach(function() {
            TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions.and.callThrough();
        });

        it("and given there was only one target value, when I change the source field's value, then the field dependencies service will be called to modify the target field and the target field's value will be set according to the dependency rule", function() {
            var target_field = {
                field_id: 58,
                values: [{ id: 694 }, { id: 924 }],
                filtered_values: [{ id: 694 }, { id: 924 }]
            };
            var target_field_value = [694];
            var field_dependencies_rules = [
                {
                    source_field_id: 65,
                    source_value_id: 478,
                    target_field_id: 58,
                    target_value_id: 924
                }
            ];
            TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues.and.returnValue(
                [{ id: 924 }]
            );
            var modal_model = controller_params.modal_model;
            modal_model.tracker = {
                fields: [target_field],
                workflow: {
                    rules: {
                        lists: field_dependencies_rules
                    }
                }
            };
            modal_model.values = {
                65: {
                    bind_value_ids: []
                },
                58: {
                    bind_value_ids: target_field_value
                }
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);
            // First apply so the watcher takes into account the initial value
            $scope.$apply();

            modal_model.values[65].bind_value_ids.push(478);
            $scope.$apply();

            expect(
                TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues
            ).toHaveBeenCalledWith([478], target_field, field_dependencies_rules);
            expect(target_field.filtered_values).toEqual([{ id: 924 }]);
            expect(target_field_value).toEqual([924]);
        });

        it("and given there were two target values, when I change the source field's value, then the field dependencies service will be called to modify the target field and the target fields's value will be reset", function() {
            var target_field = {
                field_id: 47,
                values: [{ id: 412 }, { id: 157 }],
                filtered_values: [{ id: 412 }]
            };
            var target_field_value = [412];
            var field_dependencies_rules = [
                {
                    source_field_id: 51,
                    source_value_id: 780,
                    target_field_id: 47,
                    target_value_id: 412
                },
                {
                    source_field_id: 51,
                    source_value_id: 780,
                    target_field_id: 47,
                    target_value_id: 157
                }
            ];
            TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues.and.returnValue(
                [{ id: 412 }, { id: 157 }]
            );
            var modal_model = controller_params.modal_model;
            modal_model.tracker = {
                fields: [target_field],
                workflow: {
                    rules: {
                        lists: field_dependencies_rules
                    }
                }
            };
            modal_model.values = {
                51: {
                    bind_value_ids: []
                },
                47: {
                    bind_value_ids: target_field_value
                }
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);
            // First apply so the watcher takes into account the initial value
            $scope.$apply();

            modal_model.values[51].bind_value_ids.push(780);
            $scope.$apply();

            expect(
                TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues
            ).toHaveBeenCalledWith([780], target_field, field_dependencies_rules);
            expect(target_field.filtered_values).toEqual([{ id: 412 }, { id: 157 }]);
            expect(target_field_value).toEqual([]);
        });
    });

    describe("", () => {
        beforeEach(() => {
            ArtifactModalController = $controller(BaseModalController, controller_params);
        });

        describe("isDisabled() -", () => {
            describe("Given that the modal was opened in creation mode", () => {
                it("and given a field that had the 'create' permission, when I check if it is disabled, then it will return false", () => {
                    isInCreationMode.and.returnValue(true);
                    const field = {
                        permissions: ["read", "update", "create"]
                    };

                    const result = ArtifactModalController.isDisabled(field);

                    expect(result).toBe(false);
                });

                it("and given a field that didn't have the 'create' permission, when I check if it is disabled then it will return true", function() {
                    isInCreationMode.and.returnValue(true);
                    const field = {
                        permissions: ["read", "update"]
                    };

                    const result = ArtifactModalController.isDisabled(field);

                    expect(result).toBe(true);
                });
            });

            describe("Given that the modal was opened in edition mode", () => {
                it("and given a field that had the 'update' permission, when I check if it is disabled then it will return false", () => {
                    isInCreationMode.and.returnValue(false);
                    const field = {
                        permissions: ["read", "update", "create"]
                    };

                    const result = ArtifactModalController.isDisabled(field);

                    expect(result).toBe(false);
                });

                it("and given a field that didn't have the 'update' permission, when I check if it is disabled then it will return true", () => {
                    isInCreationMode.and.returnValue(false);
                    const field = {
                        permissions: ["read", "create"]
                    };

                    const result = ArtifactModalController.isDisabled(field);

                    expect(result).toBe(true);
                });
            });
        });

        describe("formatColor() -", () => {
            it("Given color with camel case, when I format then it will return a kebab case color", () => {
                const color = "inca_silver";

                const result = ArtifactModalController.formatColor(color);

                expect(result).toBe("inca-silver");
            });

            it("Given color with several camel case, when I format then it will return a kebab case color", () => {
                const color = "lake_placid_blue";

                const result = ArtifactModalController.formatColor(color);

                expect(result).toBe("lake-placid-blue");
            });
        });
    });
});

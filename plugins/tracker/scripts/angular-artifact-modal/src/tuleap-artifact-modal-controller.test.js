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

import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

import BaseModalController from "./tuleap-artifact-modal-controller.js";

import * as modal_create_mode_state from "./modal-creation-mode-state.js";
import * as rest_service from "./rest/rest-service.js";
import * as file_field_detector from "./tuleap-artifact-modal-fields/file-field/file-field-detector.js";
import * as file_uploader from "./tuleap-artifact-modal-fields/file-field/file-uploader.js";
import * as is_uploading_in_ckeditor_state from "./tuleap-artifact-modal-fields/file-field/is-uploading-in-ckeditor-state.js";

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
        createArtifact,
        editArtifact,
        getAllFileFields,
        uploadAllTemporaryFiles,
        isUploadingInCKEditor;

    beforeEach(() => {
        angular.mock.module(artifact_modal_module, function ($provide) {
            $provide.decorator("TuleapArtifactModalValidateService", function ($delegate) {
                jest.spyOn($delegate, "validateArtifactFieldsValues").mockImplementation(() => {});

                return $delegate;
            });

            $provide.decorator("TuleapArtifactModalFieldDependenciesService", function ($delegate) {
                jest.spyOn($delegate, "getTargetFieldPossibleValues").mockImplementation(() => {});
                jest.spyOn($delegate, "setUpFieldDependenciesActions");

                return $delegate;
            });
        });

        angular.mock.inject(function (
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

            tlp_modal = {
                hide: jest.fn(),
                addEventListener: jest.fn(),
            };
            const modal_instance = {
                tlp_modal: tlp_modal,
            };

            mockCallback = jest.fn();
            $scope = $rootScope.$new();

            $controller = _$controller_;
            controller_params = {
                $scope: $scope,
                modal_instance: modal_instance,
                modal_model: {
                    title: {
                        content: "",
                    },
                    ordered_fields: [
                        {
                            label: "field01",
                        },
                        {
                            label: "fieldset01",
                            is_hidden: true,
                        },
                        {
                            label: "fieldset02",
                            is_hidden: false,
                        },
                    ],
                    color: "inca_silver",
                },
                TuleapArtifactModalValidateService,
                TuleapArtifactModalLoading,
                TuleapArtifactModalFieldDependenciesService,
                displayItemCallback: mockCallback,
            };
        });

        isInCreationMode = jest.spyOn(modal_create_mode_state, "isInCreationMode");
        createArtifact = jest.spyOn(rest_service, "createArtifact");
        editArtifact = jest.spyOn(rest_service, "editArtifact");
        getAllFileFields = jest.spyOn(file_field_detector, "getAllFileFields");
        uploadAllTemporaryFiles = jest.spyOn(file_uploader, "uploadAllTemporaryFiles");
        isUploadingInCKEditor = jest.spyOn(is_uploading_in_ckeditor_state, "isUploadingInCKEditor");
    });

    describe("init() -", function () {
        beforeEach(function () {
            jest.spyOn($scope, "$watch").mockImplementation(() => {});
        });

        it("when I load the controller, then field dependencies watchers will be set once for each different source field", function () {
            controller_params.modal_model.tracker = {
                fields: [{ field_id: 22 }],
                workflow: {
                    rules: {
                        lists: [
                            {
                                source_field_id: 43,
                                source_value_id: 752,
                                target_field_id: 22,
                                target_value_id: 519,
                            },
                        ],
                    },
                },
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();

            expect($scope.$watch).toHaveBeenCalledTimes(1);
            expect(
                TuleapArtifactModalFieldDependenciesService.setUpFieldDependenciesActions
            ).toHaveBeenCalledWith(controller_params.modal_model.tracker, expect.any(Function));
        });

        it(`Given no title semantic, when I load the controller,
                then the title will be an empty string`, () => {
            controller_params.modal_model.title = null;

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();

            expect(ArtifactModalController.title).toEqual("");
        });

        it(`when I close the modal, then it will reset the isUploadingInCKEditor state`, () => {
            const spySetIsNotUploadingInCKEditor = jest.spyOn(
                is_uploading_in_ckeditor_state,
                "setIsNotUploadingInCKEditor"
            );
            controller_params.modal_instance.tlp_modal = {
                addEventListener: jest.fn((event_name, handler) => {
                    handler();
                }),
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();

            expect(spySetIsNotUploadingInCKEditor).toHaveBeenCalled();
        });
    });

    describe("submit() - Given a tracker id, field values, a callback function", () => {
        beforeEach(() => {
            TuleapArtifactModalValidateService.validateArtifactFieldsValues.mockImplementation(
                (values) => values
            );
            isUploadingInCKEditor.mockReturnValue(false);
            getAllFileFields.mockReturnValue([]);
        });

        it(`and given that an upload is still ongoing in CKEditor,
            then it does nothing`, () => {
            isUploadingInCKEditor.mockReturnValue(true);

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.submit();

            expect(createArtifact).not.toHaveBeenCalled();
            expect(editArtifact).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
        });

        it(`and no artifact_id,
            when I submit the modal to Tuleap,
            then the field values will be validated,
            the artifact will be created ,
            the modal will be closed
            and the callback will be called`, () => {
            createArtifact.mockReturnValue($q.resolve({ id: 3042 }));
            isInCreationMode.mockReturnValue(true);
            controller_params.modal_model.tracker_id = 39;
            ArtifactModalController = $controller(BaseModalController, controller_params);
            const values = [
                { field_id: 359, value: 907 },
                { field_id: 613, bind_value_ids: [919] },
            ];
            ArtifactModalController.values = values;
            const followup_comment = { body: "My comment", format: "text" };
            ArtifactModalController.new_followup_comment = followup_comment;

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            $scope.$apply();

            expect(
                TuleapArtifactModalValidateService.validateArtifactFieldsValues
            ).toHaveBeenCalledWith(values, true, followup_comment);
            expect(createArtifact).toHaveBeenCalledWith(39, values);
            expect(editArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
            expect(mockCallback).toHaveBeenCalled();
        });

        it(`and an artifact_id to edit,
            when I submit the modal to Tuleap,
            then the field values will be validated,
            the artifact will be edited,
            the modal will be closed
            and the callback will be called`, () => {
            const edit_request = $q.defer();
            editArtifact.mockReturnValue(edit_request.promise);
            isInCreationMode.mockReturnValue(false);
            controller_params.modal_model.artifact_id = 8155;
            controller_params.modal_model.tracker_id = 186;
            ArtifactModalController = $controller(BaseModalController, controller_params);
            const values = [
                { field_id: 983, value: 741 },
                { field_id: 860, bind_value_ids: [754] },
            ];
            const followup_comment = { body: "My comment", format: "text" };
            ArtifactModalController.values = values;
            ArtifactModalController.new_followup_comment = followup_comment;

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            edit_request.resolve({ id: 8155 });
            $scope.$apply();

            expect(
                TuleapArtifactModalValidateService.validateArtifactFieldsValues
            ).toHaveBeenCalledWith(values, false, followup_comment);
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
                value: [66],
            };
            var second_file_field_value = {
                field_id: 277,
                temporary_files: second_field_temporary_files,
                type: "file",
                value: [],
            };
            var first_upload = $q.defer();
            var second_upload = $q.defer();
            uploadAllTemporaryFiles.mockImplementation((temporary_files) => {
                switch (temporary_files[0].description) {
                    case "one":
                        return first_upload.promise;
                    case "two":
                        return second_upload.promise;
                    default:
                        return $q.reject();
                }
            });
            var edit_request = $q.defer();
            editArtifact.mockReturnValue(edit_request.promise);
            var values = [first_file_field_value, second_file_field_value];
            ArtifactModalController.values = values;
            getAllFileFields.mockReturnValue(values);

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
            isInCreationMode.mockReturnValue(false);
            editArtifact.mockReturnValue($q.reject());
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.values = [];

            ArtifactModalController.submit();
            $scope.$apply();

            expect(tlp_modal.hide).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
        });
    });

    describe("Field dependency watchers - Given a field dependency rule was defined in the tracker", function () {
        it("and given there was only one target value, when I change the source field's value, then the field dependencies service will be called to modify the target field and the target field's value will be set according to the dependency rule", function () {
            var target_field = {
                field_id: 58,
                values: [{ id: 694 }, { id: 924 }],
                filtered_values: [{ id: 694 }, { id: 924 }],
            };
            var target_field_value = [694];
            var field_dependencies_rules = [
                {
                    source_field_id: 65,
                    source_value_id: 478,
                    target_field_id: 58,
                    target_value_id: 924,
                },
            ];
            TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues.mockReturnValue(
                [{ id: 924 }]
            );
            var modal_model = controller_params.modal_model;
            modal_model.tracker = {
                fields: [target_field],
                workflow: {
                    rules: {
                        lists: field_dependencies_rules,
                    },
                },
            };
            modal_model.values = {
                65: {
                    bind_value_ids: [],
                },
                58: {
                    bind_value_ids: target_field_value,
                },
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();
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

        it("and given there were two target values, when I change the source field's value, then the field dependencies service will be called to modify the target field and the target fields's value will be reset", function () {
            var target_field = {
                field_id: 47,
                values: [{ id: 412 }, { id: 157 }],
                filtered_values: [{ id: 412 }],
            };
            var target_field_value = [412];
            var field_dependencies_rules = [
                {
                    source_field_id: 51,
                    source_value_id: 780,
                    target_field_id: 47,
                    target_value_id: 412,
                },
                {
                    source_field_id: 51,
                    source_value_id: 780,
                    target_field_id: 47,
                    target_value_id: 157,
                },
            ];
            TuleapArtifactModalFieldDependenciesService.getTargetFieldPossibleValues.mockReturnValue(
                [{ id: 412 }, { id: 157 }]
            );
            var modal_model = controller_params.modal_model;
            modal_model.tracker = {
                fields: [target_field],
                workflow: {
                    rules: {
                        lists: field_dependencies_rules,
                    },
                },
            };
            modal_model.values = {
                51: {
                    bind_value_ids: [],
                },
                47: {
                    bind_value_ids: target_field_value,
                },
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();
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

    describe("Can manipulate the fields and comments", () => {
        beforeEach(() => {
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();
        });

        describe(`setFieldValue()`, () => {
            it(`Given a field id, it returns a function
                that updates the field's value model`, () => {
                ArtifactModalController.values = { 190: { value: "Some value" } };

                const updater = ArtifactModalController.setFieldValue(190);
                updater("Changed value");

                expect(ArtifactModalController.values[190].value).toEqual("Changed value");
            });
        });

        describe(`addToFilesAddedByTextField()`, () => {
            it(`Given a file field id and an uploaded file,
                it adds the file to the field's value model`, () => {
                ArtifactModalController.values = {
                    204: {
                        images_added_by_text_fields: [
                            {
                                id: 180,
                                download_href: "https://example.com/sacrilegiously",
                            },
                            { id: 59, download_href: "https://example.com/swinishly" },
                        ],
                    },
                };

                const uploaded_file = {
                    id: 16,
                    download_href: "https://example.com/microthorax",
                };
                ArtifactModalController.addToFilesAddedByTextField(204, uploaded_file);

                expect(ArtifactModalController.values[204].images_added_by_text_fields).toContain(
                    uploaded_file
                );
                expect(ArtifactModalController.values[204].value).toContain(uploaded_file.id);
            });
        });

        describe(`setFollowupComment()`, () => {
            it(`Given a comment object, it sets the new_followup_comment with the new object`, () => {
                const comment = {
                    format: "html",
                    body: `<p>Lorem ipsum dolor sit amet</p>`,
                };
                ArtifactModalController.setFollowupComment(comment);

                expect(ArtifactModalController.new_followup_comment).toEqual(comment);
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

        describe("extractHiddenFieldsets()", () => {
            it("Given the modal is in creation mode, when I open it then there are no hidden fieldsets defined", () => {
                isInCreationMode.mockReturnValue(true);
                ArtifactModalController = $controller(BaseModalController, controller_params);
                ArtifactModalController.$onInit();

                expect(ArtifactModalController.hidden_fieldsets).toEqual([]);
            });

            it("Given the modal is not in creation mode, when I open it then there are hidden fieldsets defined", () => {
                isInCreationMode.mockReturnValue(false);
                ArtifactModalController = $controller(BaseModalController, controller_params);
                ArtifactModalController.$onInit();

                expect(ArtifactModalController.hidden_fieldsets).toEqual([
                    { label: "fieldset01", is_hidden: true },
                ]);
            });
        });
    });
});

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

import * as modal_create_mode_state from "./modal-creation-mode-state";
import * as rest_service from "./rest/rest-service";
import * as file_field_detector from "./fields/file-field/file-field-detector";
import * as is_uploading_in_ckeditor_state from "./fields/file-field/is-uploading-in-ckeditor-state";
import * as field_dependencies_helper from "./field-dependencies-helper.js";
import { getTargetFieldPossibleValues } from "./field-dependencies-helper.js";
import * as fields_validator from "./validate-artifact-field-value.js";

const PROJECT_ID = 133;

describe("TuleapArtifactModalController", () => {
    let $scope,
        $q,
        $controller,
        controller_params,
        ArtifactModalController,
        tlp_modal,
        TuleapArtifactModalLoading,
        mockCallback,
        isInCreationMode,
        createArtifact,
        editArtifact,
        editArtifactWithConcurrencyChecking,
        getAllFileFields,
        isUploadingInCKEditor,
        validateValues;

    beforeEach(() => {
        angular.mock.module(artifact_modal_module, function () {});

        angular.mock.inject(function (
            _$controller_,
            $rootScope,
            _$q_,
            _$timeout_,
            _TuleapArtifactModalLoading_
        ) {
            $q = _$q_;
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
                    tracker: {
                        item_name: "story",
                        project: { id: PROJECT_ID },
                    },
                },
                TuleapArtifactModalLoading,
                displayItemCallback: mockCallback,
            };
        });

        isInCreationMode = jest.spyOn(modal_create_mode_state, "isInCreationMode");
        createArtifact = jest.spyOn(rest_service, "createArtifact");
        editArtifact = jest.spyOn(rest_service, "editArtifact");
        editArtifactWithConcurrencyChecking = jest.spyOn(
            rest_service,
            "editArtifactWithConcurrencyChecking"
        );
        getAllFileFields = jest.spyOn(file_field_detector, "getAllFileFields");
        isUploadingInCKEditor = jest.spyOn(is_uploading_in_ckeditor_state, "isUploadingInCKEditor");
        validateValues = jest.spyOn(fields_validator, "validateArtifactFieldsValues");
    });

    describe("init() -", function () {
        beforeEach(function () {
            jest.spyOn($scope, "$watch").mockImplementation(() => {});
        });

        it("when I load the controller, then field dependencies watchers will be set once for each different source field which are submittable", function () {
            jest.spyOn(field_dependencies_helper, "setUpFieldDependenciesActions");
            controller_params.modal_model.tracker = {
                project: { id: PROJECT_ID },
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
                            {
                                source_field_id: 112,
                                source_value_id: 666,
                                target_field_id: 42,
                                target_value_id: 777,
                            },
                        ],
                    },
                },
            };

            ArtifactModalController = $controller(BaseModalController, controller_params);

            const values = {
                43: { field_id: 43, bind_value_ids: [907] },
            };

            ArtifactModalController.values = values;
            ArtifactModalController.$onInit();

            expect($scope.$watch).toHaveBeenCalledTimes(1);
            expect(field_dependencies_helper.setUpFieldDependenciesActions).toHaveBeenCalledWith(
                controller_params.modal_model.tracker,
                expect.any(Function)
            );
        });

        it(`Given no title semantic, when I load the controller,
                then the title will be an empty string`, () => {
            controller_params.modal_model.title = null;

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();

            expect(ArtifactModalController.title).toBe("");
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
            validateValues.mockImplementation((values) => values);
            isUploadingInCKEditor.mockReturnValue(false);
            getAllFileFields.mockReturnValue([]);
            TuleapArtifactModalLoading.loading = false;
        });

        function mockSuccessfulUpload() {
            jest.spyOn(ArtifactModalController, "uploadAllFileFields").mockReturnValue(
                $q.when(undefined)
            );
        }

        it(`and given that an upload is still ongoing in CKEditor,
            then it does nothing`, () => {
            isUploadingInCKEditor.mockReturnValue(true);

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.submit();
            $scope.$apply();

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
            mockSuccessfulUpload();

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            $scope.$apply();

            expect(validateValues).toHaveBeenCalledWith(
                values,
                true,
                followup_comment,
                expect.any(Object)
            );
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
            editArtifactWithConcurrencyChecking.mockReturnValue($q.when({ id: 8155 }));
            isInCreationMode.mockReturnValue(false);
            controller_params.modal_model.artifact_id = 8155;
            controller_params.modal_model.last_changeset_id = 78;
            controller_params.modal_model.tracker_id = 186;
            controller_params.modal_model.etag = "etag";
            controller_params.modal_model.last_modified = 1629098929;
            ArtifactModalController = $controller(BaseModalController, controller_params);
            const values = [
                { field_id: 983, value: 741 },
                { field_id: 860, bind_value_ids: [754] },
            ];
            const followup_comment = { body: "My comment", format: "text" };
            ArtifactModalController.values = values;
            ArtifactModalController.new_followup_comment = followup_comment;
            mockSuccessfulUpload();

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            $scope.$apply();

            expect(validateValues).toHaveBeenCalledWith(
                values,
                false,
                followup_comment,
                expect.any(Object)
            );
            expect(editArtifactWithConcurrencyChecking).toHaveBeenCalledWith(
                8155,
                values,
                followup_comment,
                "etag",
                1629098929
            );
            expect(createArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
            expect(mockCallback).toHaveBeenCalledWith(8155);
        });

        it("and given the server responded an error, when I submit the modal to Tuleap, then the modal will not be closed and the callback won't be called", () => {
            isInCreationMode.mockReturnValue(false);
            editArtifact.mockReturnValue($q.reject());
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.values = [];
            ArtifactModalController.confirm_action_to_edit = true;
            mockSuccessfulUpload();

            ArtifactModalController.submit();
            $scope.$apply();

            expect(tlp_modal.hide).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
        });

        it("and given user force to edit artifact in concurrency mode, then the modal will be closed, and the artifact will be edited", () => {
            const edit_request = $q.defer();
            editArtifact.mockReturnValue(edit_request.promise);
            isInCreationMode.mockReturnValue(false);
            controller_params.modal_model.artifact_id = 8155;
            controller_params.modal_model.last_changeset_id = 78;
            controller_params.modal_model.tracker_id = 186;

            ArtifactModalController = $controller(BaseModalController, controller_params);
            const values = [
                { field_id: 983, value: 741 },
                { field_id: 860, bind_value_ids: [754] },
            ];
            const followup_comment = { body: "My comment", format: "text" };
            ArtifactModalController.values = values;
            ArtifactModalController.new_followup_comment = followup_comment;
            ArtifactModalController.confirm_action_to_edit = true;
            mockSuccessfulUpload();

            ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBeTruthy();
            edit_request.resolve({ id: 8155 });
            $scope.$apply();

            expect(validateValues).toHaveBeenCalledWith(
                values,
                false,
                followup_comment,
                expect.any(Object)
            );
            expect(editArtifact).toHaveBeenCalledWith(8155, values, followup_comment);
            expect(createArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(TuleapArtifactModalLoading.loading).toBeFalsy();
            expect(mockCallback).toHaveBeenCalledWith(8155);
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
            jest.spyOn(field_dependencies_helper, "getTargetFieldPossibleValues").mockReturnValue([
                { id: 924 },
            ]);
            var modal_model = controller_params.modal_model;
            modal_model.tracker = {
                project: { id: PROJECT_ID },
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

            expect(field_dependencies_helper.getTargetFieldPossibleValues).toHaveBeenCalledWith(
                [478],
                target_field,
                field_dependencies_rules
            );
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
            jest.spyOn(field_dependencies_helper, "getTargetFieldPossibleValues").mockReturnValue([
                { id: 412 },
                { id: 157 },
            ]);

            var modal_model = controller_params.modal_model;
            modal_model.tracker = {
                project: { id: PROJECT_ID },
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

            expect(getTargetFieldPossibleValues).toHaveBeenCalledWith(
                [780],
                target_field,
                field_dependencies_rules
            );
            expect(target_field.filtered_values).toEqual([{ id: 412 }, { id: 157 }]);
            expect(target_field_value).toEqual([]);
        });
    });

    describe("Can manipulate the fields and comments", () => {
        beforeEach(() => {
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();
        });

        describe(`setFieldValueForComputedFieldElement()`, () => {
            it(`Given an event, it will update the computed field's model`, () => {
                ArtifactModalController.values = {
                    552: {
                        is_autocomputed: true,
                        manual_value: null,
                    },
                };

                ArtifactModalController.setFieldValueForComputedFieldElement(
                    new CustomEvent("value-changed", {
                        detail: { field_id: 552, autocomputed: false, manual_value: 67 },
                    })
                );

                expect(ArtifactModalController.values[552].is_autocomputed).toBe(false);
                expect(ArtifactModalController.values[552].manual_value).toBe(67);
            });
        });

        describe(`addToFilesAddedByTextField()`, () => {
            it(`Given an event containing file field id and an uploaded file,
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
                ArtifactModalController.addToFilesAddedByTextField(
                    new CustomEvent("upload-image", {
                        detail: { field_id: 204, image: uploaded_file },
                    })
                );

                expect(ArtifactModalController.values[204].images_added_by_text_fields).toContain(
                    uploaded_file
                );
                expect(ArtifactModalController.values[204].value).toContain(uploaded_file.id);
            });
        });

        describe(`setFollowupComment()`, () => {
            it(`Given an event, it will set the new_followup_comment with the detail`, () => {
                const format = "html";
                const body = `<p>Lorem ipsum dolor sit amet</p>`;
                ArtifactModalController.setFollowupComment(
                    new CustomEvent("value-changed", { detail: { format, body } })
                );

                expect(ArtifactModalController.new_followup_comment.format).toBe(format);
                expect(ArtifactModalController.new_followup_comment.body).toBe(body);
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

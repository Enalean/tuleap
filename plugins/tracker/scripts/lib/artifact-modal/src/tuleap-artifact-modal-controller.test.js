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

import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { Option } from "@tuleap/option";
import * as modal_create_mode_state from "./modal-creation-mode-state";
import * as rest_service from "./rest/rest-service";
import * as file_field_detector from "./adapters/UI/fields/file-field/file-field-detector";
import * as fields_validator from "./validate-artifact-field-value.js";
import * as field_dependencies_helper from "./domain/fields/select-box-field/FieldDependenciesValuesHelper";
import { setCatalog } from "./gettext-catalog";

const PROJECT_ID = 133;

describe("TuleapArtifactModalController", () => {
    let $scope,
        $controller,
        controller_params,
        ArtifactModalController,
        tlp_modal,
        TuleapArtifactModalLoading,
        mockCallback,
        isInCreationMode,
        editArtifact,
        editArtifactWithConcurrencyChecking,
        getAllFileFields,
        validateValues;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        angular.mock.module(artifact_modal_module, function () {});

        angular.mock.inject(function (
            _$controller_,
            $rootScope,
            _$timeout_,
            _TuleapArtifactModalLoading_
        ) {
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
                        workflow: {
                            rules: {
                                lists: [],
                            },
                        },
                    },
                },
                TuleapArtifactModalLoading,
                displayItemCallback: mockCallback,
            };
        });

        const spy_create_artifact_feature_flag = jest.spyOn(fetch_result, "getJSON");
        spy_create_artifact_feature_flag.mockReturnValue(okAsync("1"));

        isInCreationMode = jest.spyOn(modal_create_mode_state, "isInCreationMode");
        editArtifact = jest.spyOn(rest_service, "editArtifact");
        editArtifactWithConcurrencyChecking = jest.spyOn(
            rest_service,
            "editArtifactWithConcurrencyChecking"
        );
        getAllFileFields = jest.spyOn(file_field_detector, "getAllFileFields");
        validateValues = jest.spyOn(fields_validator, "validateArtifactFieldsValues");
    });

    describe("init() -", function () {
        beforeEach(function () {
            jest.spyOn($scope, "$watch").mockImplementation(() => {});
        });

        it("when I load the controller, then field dependencies watchers will be set once for each different source field which are submittable", function () {
            jest.spyOn(field_dependencies_helper, "FieldDependenciesValuesHelper");
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
            ArtifactModalController.values = {
                43: { field_id: 43, bind_value_ids: [907] },
            };
            ArtifactModalController.$onInit();

            expect(field_dependencies_helper.FieldDependenciesValuesHelper).toHaveBeenCalledWith(
                expect.any(Object),
                controller_params.modal_model.tracker.workflow.rules.lists
            );
        });

        it(`Given no title semantic, when I load the controller,
                then the title will be an empty string`, () => {
            controller_params.modal_model.title = null;

            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.$onInit();

            expect(ArtifactModalController.title).toBe("");
        });
    });

    describe("submit() - Given a tracker id, field values, a callback function", () => {
        beforeEach(() => {
            validateValues.mockImplementation((values) => values);
            getAllFileFields.mockReturnValue([]);
            TuleapArtifactModalLoading.loading = false;
        });

        it(`when submit is disabled, it does nothing`, async () => {
            const createArtifact = jest.spyOn(fetch_result, "postJSON");
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.submit_disabling_reason = Option.fromValue("Disabled");
            await ArtifactModalController.submit();

            expect(createArtifact).not.toHaveBeenCalled();
            expect(editArtifact).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
        });

        it(`and no artifact_id,
            when I submit the modal,
            then the field values will be validated,
            the artifact will be created,
            the modal will be closed
            and the callback will be called`, async () => {
            const createArtifact = jest
                .spyOn(fetch_result, "postJSON")
                .mockReturnValue(okAsync({ id: 3042 }));
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

            const promise = ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBe(true);
            await promise;

            expect(validateValues).toHaveBeenCalledWith(
                values,
                true,
                followup_comment,
                expect.any(Object)
            );
            expect(createArtifact).toHaveBeenCalled();
            expect(editArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(mockCallback).toHaveBeenCalled();
            $scope.$apply();
            expect(TuleapArtifactModalLoading.loading).toBe(false);
        });

        it(`and an artifact_id to edit,
            when I submit the modal,
            then the field values will be validated,
            the artifact will be edited,
            the modal will be closed
            and the callback will be called`, async () => {
            const createArtifact = jest.spyOn(fetch_result, "postJSON");
            editArtifactWithConcurrencyChecking.mockResolvedValue({ id: 8155 });
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

            const promise = ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBe(true);
            await promise;

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
            expect(mockCallback).toHaveBeenCalledWith(8155);
            $scope.$apply();
            expect(TuleapArtifactModalLoading.loading).toBe(false);
        });

        it(`when I submit the modal and the server responds with an error,
            then the modal will not be closed and the callback won't be called`, async () => {
            isInCreationMode.mockReturnValue(false);
            editArtifact.mockRejectedValue();
            ArtifactModalController = $controller(BaseModalController, controller_params);
            ArtifactModalController.values = [];
            ArtifactModalController.confirm_action_to_edit = true;

            await ArtifactModalController.submit();

            expect(tlp_modal.hide).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
            $scope.$apply();
            expect(TuleapArtifactModalLoading.loading).toBe(false);
        });

        it(`and given user forced to edit the artifact despite the concurrent edit warning,
            then the modal will be closed, and the artifact will be edited`, async () => {
            const createArtifact = jest.spyOn(fetch_result, "postJSON");
            editArtifact.mockResolvedValue({ id: 8155 });
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

            const promise = ArtifactModalController.submit();
            expect(TuleapArtifactModalLoading.loading).toBe(true);
            await promise;

            expect(validateValues).toHaveBeenCalledWith(
                values,
                false,
                followup_comment,
                expect.any(Object)
            );
            expect(editArtifact).toHaveBeenCalledWith(8155, values, followup_comment);
            expect(createArtifact).not.toHaveBeenCalled();
            expect(tlp_modal.hide).toHaveBeenCalled();
            expect(mockCallback).toHaveBeenCalledWith(8155);
            $scope.$apply();
            expect(TuleapArtifactModalLoading.loading).toBe(false);
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

                expect(ArtifactModalController.hidden_fieldsets).toStrictEqual([]);
            });

            it("Given the modal is not in creation mode, when I open it then there are hidden fieldsets defined", () => {
                isInCreationMode.mockReturnValue(false);
                ArtifactModalController = $controller(BaseModalController, controller_params);
                ArtifactModalController.$onInit();

                expect(ArtifactModalController.hidden_fieldsets).toStrictEqual([
                    { label: "fieldset01", is_hidden: true },
                ]);
            });
        });
    });
});

import permission_field_module from "./permission-field.js";
import angular from "angular";
import "angular-mocks";

import BasePermissionFieldController from "./permission-field-controller.js";

describe("TuleapArtifactModalPermissionFieldController", function () {
    var TuleapArtifactModalPermissionFieldController;

    beforeEach(function () {
        angular.mock.module(permission_field_module);

        var $controller;

        angular.mock.inject(function (_$controller_) {
            $controller = _$controller_;
        });

        TuleapArtifactModalPermissionFieldController = $controller(BasePermissionFieldController);
        TuleapArtifactModalPermissionFieldController.value_model = {
            value: {
                is_used_by_default: null,
                granted_groups: [],
            },
        };
        TuleapArtifactModalPermissionFieldController.field = {
            required: false,
        };
        // eslint-disable-next-line jest/prefer-spy-on
        TuleapArtifactModalPermissionFieldController.isDisabled = jest.fn();
    });

    describe("clearSelectBox()", function () {
        it("Given that the checkbox was checked, when it is unchecked, then the granted_groups will be emptied", function () {
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = null;
            TuleapArtifactModalPermissionFieldController.value_model.value.granted_groups = [
                "2",
                "102_3",
            ];

            TuleapArtifactModalPermissionFieldController.clearSelectBox();

            expect(
                TuleapArtifactModalPermissionFieldController.value_model.value.granted_groups
            ).toEqual([]);
        });

        it("Given that the checkbox was not checked and the granted_groups were empty, when it is checked, then the granted_groups will be set to an empty array", function () {
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = true;
            TuleapArtifactModalPermissionFieldController.value_model.value.granted_groups = [];

            TuleapArtifactModalPermissionFieldController.clearSelectBox();

            expect(
                TuleapArtifactModalPermissionFieldController.value_model.value.granted_groups
            ).toEqual([]);
        });
    });

    describe("isSelectBoxDisabled()", function () {
        it("Given that the field was required and not disabled, then false will be returned", function () {
            TuleapArtifactModalPermissionFieldController.isDisabled.mockReturnValue(true);
            TuleapArtifactModalPermissionFieldController.field.required = true;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxDisabled();

            expect(result).toBe(true);
        });

        it("Given that the field was disabled and the checkbox was not checked, then true will be returned", function () {
            TuleapArtifactModalPermissionFieldController.isDisabled.mockReturnValue(true);
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = null;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxDisabled();

            expect(result).toBe(true);
        });

        it("Given that the field was disabled and the checkbox was checked, then true will be returned", function () {
            TuleapArtifactModalPermissionFieldController.isDisabled.mockReturnValue(true);
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = true;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxDisabled();

            expect(result).toBe(true);
        });

        it("Given that the field was not disabled and the checkbox was not checked, then true will be returned", function () {
            TuleapArtifactModalPermissionFieldController.isDisabled.mockReturnValue(false);
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = null;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxDisabled();

            expect(result).toBe(true);
        });

        it("Given that the field was not disabled and the checkbox was checked, then false will be returned", function () {
            TuleapArtifactModalPermissionFieldController.isDisabled.mockReturnValue(false);
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = true;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxDisabled();

            expect(result).toBe(false);
        });
    });

    describe("isSelectBoxRequired()", function () {
        it("Given that the checkbox was checked, then true will be returned", function () {
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = true;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxRequired();

            expect(result).toBe(true);
        });

        it("Given that the checkbox wasn't checked and the field was required, then true will be returned", function () {
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = null;
            TuleapArtifactModalPermissionFieldController.field.required = true;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxRequired();

            expect(result).toBe(true);
        });

        it("Given that the checkbox wasn't checked and the field was not required, then false will be returned", function () {
            TuleapArtifactModalPermissionFieldController.value_model.value.is_used_by_default = null;
            TuleapArtifactModalPermissionFieldController.field.required = false;

            var result = TuleapArtifactModalPermissionFieldController.isSelectBoxRequired();

            expect(result).toBe(false);
        });
    });
});

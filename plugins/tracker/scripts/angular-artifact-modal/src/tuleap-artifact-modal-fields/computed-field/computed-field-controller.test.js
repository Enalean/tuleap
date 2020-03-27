import computed_field_module from "./computed-field.js";
import angular from "angular";
import "angular-mocks";

import BaseComputedFieldController from "./computed-field-controller.js";

describe("ComputedFieldController", function () {
    var ComputedFieldController;

    beforeEach(function () {
        angular.mock.module(computed_field_module);

        var $controller;

        angular.mock.inject(function (_$controller_) {
            $controller = _$controller_;
        });

        ComputedFieldController = $controller(BaseComputedFieldController, {});
        ComputedFieldController.value_model = {
            value: null,
            is_autocomputed: false,
        };
        ComputedFieldController.field = {
            value: 8,
        };
    });

    describe("switchToAutocomputed() -", function () {
        it("When I switch the computed field to autocomputed, then its manual_value will be set to null and its is_autocomputed flag will be true", function () {
            ComputedFieldController.value_model.manual_value = 6;
            ComputedFieldController.value_model.is_autocomputed = false;

            ComputedFieldController.switchToAutocomputed();

            expect(ComputedFieldController.value_model.manual_value).toBe(null);
            expect(ComputedFieldController.value_model.is_autocomputed).toBe(true);
        });
    });

    describe("switchToManual() -", function () {
        it("When I switch the computed field to manual, then its is_autocomputed flag will be false", function () {
            ComputedFieldController.value_model.is_autocomputed = true;

            ComputedFieldController.switchToManual();

            expect(ComputedFieldController.field.value).toEqual(8);
            expect(ComputedFieldController.value_model.is_autocomputed).toBe(false);
        });
    });
});

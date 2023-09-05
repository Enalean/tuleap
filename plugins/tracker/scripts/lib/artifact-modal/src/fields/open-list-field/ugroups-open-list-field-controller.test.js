import tuleap_artifact_modal_module from "../../tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

import BaseUgroupsOpenListController from "./ugroups-open-list-field-controller.js";
import * as tlp from "tlp";

jest.mock("tlp");

describe("UgroupsOpenListFieldController", function () {
    var $element, UgroupsOpenListFieldController;

    beforeEach(function () {
        angular.mock.module(tuleap_artifact_modal_module);

        var $controller;
        angular.mock.inject(function (_$controller_) {
            $controller = _$controller_;
        });

        $element = angular.element("<div></div>");

        UgroupsOpenListFieldController = $controller(BaseUgroupsOpenListController, {
            $element: $element,
        });
        UgroupsOpenListFieldController.field = {
            hint: "trapezium",
        };
        UgroupsOpenListFieldController.value_model = {
            value: {
                bind_value_objects: [],
            },
        };
    });

    describe("init() -", function () {
        it("When initializing the controller, then a select2 will be created", function () {
            $element.append(
                angular.element(
                    '<select class="tuleap-artifact-modal-open-list-ugroups"></select>',
                ),
            );
            const tlpSelect2Spy = jest.spyOn(tlp, "select2");

            UgroupsOpenListFieldController.$onInit();

            expect(tlpSelect2Spy).toHaveBeenCalled();
        });
    });

    describe("isRequiredAndEmpty() -", function () {
        it("Given that the field was required and the value model was undefined, then it will return true", function () {
            UgroupsOpenListFieldController.field.required = true;
            UgroupsOpenListFieldController.value_model.value.bind_value_objects = undefined;

            expect(UgroupsOpenListFieldController.isRequiredAndEmpty()).toBe(true);
        });

        it("Given that the field was required and the value model had a value, then it will return false", function () {
            UgroupsOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    id: "772",
                    key: "unquestioningness",
                    label: "unquestioningness",
                    short_name: "unquestioningness",
                    uri: "user_groups/772",
                    users_uri: "user_groups/772/users",
                },
            ];
            UgroupsOpenListFieldController.field.required = true;

            expect(UgroupsOpenListFieldController.isRequiredAndEmpty()).toBe(false);
        });

        it("Given that the field was not required and the value model was undefined, then it will return false", function () {
            UgroupsOpenListFieldController.field.required = false;
            UgroupsOpenListFieldController.value_model.value.bind_value_objects = undefined;

            expect(UgroupsOpenListFieldController.isRequiredAndEmpty()).toBe(false);
        });
    });
});

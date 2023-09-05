import tuleap_artifact_modal_module from "../../tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

import BaseStaticOpenListController from "./static-open-list-field-controller.js";
import * as tlp from "tlp";

jest.mock("tlp");

describe("StaticOpenListFieldController", function () {
    var $scope, $rootScope, $element, StaticOpenListFieldController;

    beforeEach(function () {
        angular.mock.module(tuleap_artifact_modal_module);

        var $controller;
        angular.mock.inject(function (_$controller_, _$rootScope_) {
            $controller = _$controller_;
            $rootScope = _$rootScope_;

            $scope = $rootScope.$new();
        });

        $element = angular.element("<div></div>");

        StaticOpenListFieldController = $controller(BaseStaticOpenListController, {
            $element: $element,
            $scope: $scope,
        });

        StaticOpenListFieldController.field = {
            hint: "tottery",
            values: [],
        };
        StaticOpenListFieldController.value_model = {
            value: {
                bind_value_objects: [],
            },
        };
    });

    describe("init() -", function () {
        it("When initializing the controller, then a select2 will be created and its events will be listened", function () {
            $element.append(
                angular.element('<select class="tuleap-artifact-modal-open-list-static"></select>'),
            );
            jest.spyOn($element, "on").mockImplementation(() => {});
            const tlpSelect2Spy = jest.spyOn(tlp, "select2");

            StaticOpenListFieldController.$onInit();

            expect(tlpSelect2Spy).toHaveBeenCalled();
            expect($element.on).toHaveBeenCalledWith("select2:selecting", expect.any(Function));
            expect($element.on).toHaveBeenCalledWith("select2:unselecting", expect.any(Function));
        });
    });

    describe("isRequiredAndEmpty() -", function () {
        it("Given that the field was required and the value model empty, then it will return true", function () {
            StaticOpenListFieldController.field.required = true;
            StaticOpenListFieldController.value_model.value.bind_value_objects = [];

            expect(StaticOpenListFieldController.isRequiredAndEmpty()).toBe(true);
        });

        it("Given that the field was required and the value model had a value, then it will return false", function () {
            StaticOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    id: "565",
                    label: "pembina",
                    color: null,
                },
            ];
            StaticOpenListFieldController.field.required = true;

            expect(StaticOpenListFieldController.isRequiredAndEmpty()).toBe(false);
        });

        it("Given that the field was not required and the value model empty, then it will return false", function () {
            StaticOpenListFieldController.field.required = false;
            StaticOpenListFieldController.value_model.value.bind_value_objects = [];

            expect(StaticOpenListFieldController.isRequiredAndEmpty()).toBe(false);
        });
    });

    describe("isStaticValueSelected() -", function () {
        it("Given a value from the field's possible values that was in the value model, then it will return true", function () {
            StaticOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    id: "640",
                    label: "subsecretarial",
                    color: null,
                },
            ];

            var field_value = {
                id: 640,
                label: "subsecretarial",
                is_hidden: false,
            };

            var result = StaticOpenListFieldController.isStaticValueSelected(field_value);

            expect(result).toBe(true);
        });

        it("Given a value that was not in the value model, then it will return false", function () {
            StaticOpenListFieldController.value_model.value.bind_value_objects = [];

            var field_value = {
                id: 902,
                label: "banket",
                is_hidden: false,
            };

            var result = StaticOpenListFieldController.isStaticValueSelected(field_value);

            expect(result).toBe(false);
        });
    });

    describe("handleStaticValueSelection() -", function () {
        beforeEach(function () {
            jest.spyOn($scope, "$apply");
        });

        it("Given an event with a selection, then an object with 'id' and 'label' properties will be pushed in the value model", function () {
            var event = {
                params: {
                    name: "select",
                    args: {
                        data: {
                            id: 979,
                            text: "palpate",
                        },
                    },
                },
            };

            StaticOpenListFieldController.handleStaticValueSelection(event);

            expect(StaticOpenListFieldController.value_model.value.bind_value_objects).toEqual([
                {
                    id: 979,
                    label: "palpate",
                },
            ]);
        });

        it("Given an event with a new 'tag' (not in possible values) selection, then an object with 'label' property will be pushed in the value model", function () {
            var event = {
                params: {
                    name: "select",
                    args: {
                        data: {
                            id: "peptonuria",
                            text: "peptonuria",
                            isTag: true,
                        },
                    },
                },
            };

            StaticOpenListFieldController.handleStaticValueSelection(event);

            expect(StaticOpenListFieldController.value_model.value.bind_value_objects).toEqual([
                { label: "peptonuria" },
            ]);
        });
    });

    describe("handleStaticValueUnselection() -", function () {
        it("Given an event with a static value unselection, then it will be removed from the value model", function () {
            StaticOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    id: "470",
                    label: "unriddleable",
                    color: null,
                },
            ];

            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "470",
                            text: "unriddleable",
                        },
                    },
                },
            };

            StaticOpenListFieldController.handleStaticValueUnselection(event);

            expect(StaticOpenListFieldController.value_model.value.bind_value_objects).toEqual([]);
        });

        it("Given an event with a 'tag' (not in possible values) unselection, then it will be removed from the value model", function () {
            StaticOpenListFieldController.value_model.value.bind_value_objects = [
                {
                    label: "raticide",
                },
            ];

            var event = {
                params: {
                    name: "unselect",
                    args: {
                        data: {
                            id: "raticide",
                            text: "raticide",
                            isTag: true,
                        },
                    },
                },
            };

            StaticOpenListFieldController.handleStaticValueUnselection(event);

            expect(StaticOpenListFieldController.value_model.value.bind_value_objects).toEqual([]);
        });
    });

    describe("newOpenListStaticValue() -", function () {
        it("Given blank space, then it returns null", function () {
            var new_open_value = {
                term: "   ",
            };

            var result = StaticOpenListFieldController.newOpenListStaticValue(new_open_value);

            expect(result).toBeNull();
        });

        it("Given a string, then it returns an object with 'id', 'text', and 'isTag' attributes", function () {
            var new_open_value = {
                term: "slopshop",
            };

            var result = StaticOpenListFieldController.newOpenListStaticValue(new_open_value);

            expect(result).toEqual({
                id: "slopshop",
                text: "slopshop",
                isTag: true,
            });
        });

        it("Given a string with blank space, it trims it and returns an object", function () {
            var new_open_value = {
                term: " slopshop  ",
            };

            var result = StaticOpenListFieldController.newOpenListStaticValue(new_open_value);

            expect(result).toEqual({
                id: "slopshop",
                text: "slopshop",
                isTag: true,
            });
        });

        it("Given a string that already exists in the field's possible values, then it returns null", function () {
            StaticOpenListFieldController.field.values = [
                {
                    id: 682,
                    label: "magnetotherapy",
                },
                {
                    id: 815,
                    label: "extensometer",
                },
            ];

            var new_open_value = {
                term: "extensometer",
            };

            var result = StaticOpenListFieldController.newOpenListStaticValue(new_open_value);

            expect(result).toBeNull();
        });
    });
});

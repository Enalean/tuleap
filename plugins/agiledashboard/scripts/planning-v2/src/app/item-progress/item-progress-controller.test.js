import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

import BaseController from "./item-progress-controller.js";

describe("ItemProgressController -", () => {
    let ItemProgressController, value, max_value;

    beforeEach(() => {
        angular.mock.module(planning_module);

        let $controller;
        angular.mock.inject(function (_$controller_) {
            $controller = _$controller_;
        });

        ItemProgressController = $controller(BaseController, {
            value,
            max_value,
        });
    });

    describe("getStyle() -", () => {
        it("Given a value equal to max_value, then it returns width 0%", () => {
            ItemProgressController.value = 3;
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given a value inferior to max_value, then it returns the correct percentage", () => {
            ItemProgressController.value = 1.2;
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "60%" });
        });

        it("Given a value equal to 0, then it returns width 100%", () => {
            ItemProgressController.value = 0;
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "100%" });
        });

        it("Given value is not a number, then it returns width 0%", () => {
            ItemProgressController.value = "two";
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given value is undefined, then it returns width 0%", () => {
            ItemProgressController.value = undefined;
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given value is null, then it returns width 0%", () => {
            ItemProgressController.value = null;
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given value is negative, then it returns width 0%", () => {
            ItemProgressController.value = -1;
            ItemProgressController.max_value = 3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given max_value is 0, then it returns width 0%", () => {
            ItemProgressController.value = 3;
            ItemProgressController.max_value = 0;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given max_value is not a number, then it returns width 0%", () => {
            ItemProgressController.value = 3;
            ItemProgressController.max_value = "three";

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given max_value is undefined, then it returns width 0%", () => {
            ItemProgressController.value = 3;
            ItemProgressController.max_value = undefined;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given max_value is null, then it returns width 0%", () => {
            ItemProgressController.value = 3;
            ItemProgressController.max_value = null;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });

        it("Given max_value is negative, then it returns width 0%", () => {
            ItemProgressController.value = 3;
            ItemProgressController.max_value = -3;

            expect(ItemProgressController.getStyle()).toEqual({ width: "0%" });
        });
    });
});

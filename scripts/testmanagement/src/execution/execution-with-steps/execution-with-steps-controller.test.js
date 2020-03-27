import execution_module from "../execution.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./execution-with-steps-controller.js";

describe("ExecutionWithStepsController -", () => {
    let ExecutionWithStepsController, $scope;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller;

        angular.mock.inject(function (_$controller_, $rootScope) {
            $controller = _$controller_;
            $scope = $rootScope.$new();
        });

        ExecutionWithStepsController = $controller(BaseController, {
            $scope,
        });
    });

    describe("init() -", () => {
        it("Given an execution, then the steps data will be sorted by rank for easier display", () => {
            const execution = {
                id: 802,
                definition: {
                    id: 276,
                    steps: [
                        {
                            id: 12,
                            description: "apodema Canarsee Onmun toaster Rosamond",
                            rank: 9,
                        },
                        {
                            id: 44,
                            description: "acroamatics tragicness malleate bissextile",
                            rank: 8,
                        },
                    ],
                },
                steps_results: {
                    12: {
                        step_id: 12,
                        status: "notrun",
                    },
                    44: {
                        step_id: 44,
                        status: "passed",
                    },
                },
            };

            ExecutionWithStepsController.execution = execution;

            ExecutionWithStepsController.$onInit();
            $scope.$apply();

            expect(execution.definition.steps[0]).toEqual({
                id: 44,
                description: "acroamatics tragicness malleate bissextile",
                rank: 8,
            });
            expect(execution.definition.steps[1]).toEqual({
                id: 12,
                description: "apodema Canarsee Onmun toaster Rosamond",
                rank: 9,
            });
        });
    });
});

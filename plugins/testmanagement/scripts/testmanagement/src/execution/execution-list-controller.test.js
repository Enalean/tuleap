import execution_module from "./execution.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./execution-list-controller.js";
import * as feedback_state from "../feedback-state.js";

describe("ExecutionListController -", () => {
    let $ctrl,
        $scope,
        $q,
        ExecutionService,
        SharedPropertiesService,
        ExecutionRestService,
        setError;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller;

        angular.mock.inject(function (
            _$controller_,
            $rootScope,
            _$q_,
            _ExecutionService_,
            _SharedPropertiesService_,
            _ExecutionRestService_
        ) {
            $controller = _$controller_;
            $scope = $rootScope.$new();
            $q = _$q_;
            ExecutionService = _ExecutionService_;
            SharedPropertiesService = _SharedPropertiesService_;
            ExecutionRestService = _ExecutionRestService_;
        });

        $ctrl = $controller(BaseController, {
            $scope,
            ExecutionService,
            SharedPropertiesService,
            ExecutionRestService,
        });

        setError = jest.spyOn(feedback_state, "setError");
    });

    describe("loadExecutions()", () => {
        const campaign_id = 32;
        const current_user = 511;

        beforeEach(() => {
            $scope.campaign_id = campaign_id;
            jest.spyOn(ExecutionService, "removeAllViewTestExecution").mockImplementation(() => {});
            jest.spyOn(
                ExecutionService,
                "displayPresencesForAllExecutions"
            ).mockImplementation(() => {});
            jest.spyOn(ExecutionService, "addPresenceCampaign").mockImplementation(() => {});
            jest.spyOn(ExecutionService, "loadExecutions").mockImplementation(() => {});
            jest.spyOn(
                ExecutionRestService,
                "changePresenceOnTestExecution"
            ).mockImplementation(() => {});
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockReturnValue(current_user);
        });

        it("Given we were viewing an execution, then the executions will be loaded, the current execution will be updated and the presences will be updated", () => {
            const executions = [{ id: 60 }, { id: 29 }];

            const execution_id = 29;
            $scope.execution_id = execution_id;
            ExecutionService.loadExecutions.mockReturnValue($q.when(executions));
            ExecutionRestService.changePresenceOnTestExecution.mockReturnValue($q.when());
            jest.spyOn(ExecutionService, "removeViewTestExecution").mockImplementation(() => {});
            jest.spyOn(ExecutionService, "viewTestExecution").mockImplementation(() => {});

            $ctrl.loadExecutions();
            $scope.$apply();

            expect(ExecutionService.loadExecutions).toHaveBeenCalledWith(campaign_id);
            expect(ExecutionService.removeAllViewTestExecution).toHaveBeenCalled();
            expect(ExecutionService.executions_loaded).toBe(true);
            expect(ExecutionService.displayPresencesForAllExecutions).toHaveBeenCalled();

            expect(ExecutionService.addPresenceCampaign).toHaveBeenCalledWith(current_user);
            expect(ExecutionRestService.changePresenceOnTestExecution).toHaveBeenCalledWith(
                execution_id,
                ""
            );
            expect(ExecutionService.removeViewTestExecution).toHaveBeenCalledWith("", current_user);
            expect(ExecutionService.viewTestExecution).toHaveBeenCalledWith(
                execution_id,
                current_user
            );
            expect($scope.execution_id).toEqual(execution_id);
        });

        it("Given we were not viewing an execution, then it won't be updated", () => {
            ExecutionService.loadExecutions.mockReturnValue($q.when([]));

            $ctrl.loadExecutions();
            $scope.$apply();

            expect(ExecutionService.loadExecutions).toHaveBeenCalledWith(campaign_id);
            expect(ExecutionService.removeAllViewTestExecution).toHaveBeenCalled();
            expect(ExecutionService.executions_loaded).toBe(true);
            expect(ExecutionService.displayPresencesForAllExecutions).toHaveBeenCalled();

            expect(ExecutionService.addPresenceCampaign).not.toHaveBeenCalled();
            expect(ExecutionRestService.changePresenceOnTestExecution).not.toHaveBeenCalled();
        });

        it("When there aren't any executions, then an empty state will be shown", () => {
            ExecutionService.loadExecutions.mockReturnValue($q.when([]));

            $ctrl.loadExecutions();
            $scope.$apply();

            expect($ctrl.should_show_empty_state).toBe(true);
        });

        it("When there is a REST error, then it will be shown", () => {
            ExecutionService.loadExecutions.mockReturnValue(
                $q.reject({
                    data: {
                        error: {
                            code: 401,
                            message: "Unauthorized",
                        },
                    },
                })
            );

            $ctrl.loadExecutions();
            $scope.$apply();

            expect(setError).toHaveBeenCalled();
        });
    });
});

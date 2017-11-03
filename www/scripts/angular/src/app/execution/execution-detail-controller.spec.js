import execution_module from './execution.js';
import angular          from 'angular';
import 'angular-mocks';

import BaseController from './execution-detail-controller.js';

describe("ExecutionDetailController -", () => {
    let $scope,
        $q,
        ExecutionDetailController,
        SharedPropertiesService,
        LinkedArtifactsService,
        ExecutionService;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller,
            $rootScope;

        angular.mock.inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _SharedPropertiesService_,
            _LinkedArtifactsService_,
            _ExecutionService_,
        ) {
            $controller             = _$controller_;
            $q                      = _$q_;
            $rootScope              = _$rootScope_;
            SharedPropertiesService = _SharedPropertiesService_;
            LinkedArtifactsService  = _LinkedArtifactsService_;
            ExecutionService        = _ExecutionService_;
        });

        $scope = $rootScope.$new()

        spyOn(SharedPropertiesService, "getIssueTrackerConfig").and.returnValue({
            permissions: {
                create: true,
                link  : true
            }
        });

        spyOn(ExecutionService, "loadExecutions");

        ExecutionDetailController = $controller(BaseController, {
            $scope          : $scope,
            ExecutionService: ExecutionService,
        });
    });

    describe("refreshLinkedIssues() -", () => {
        beforeEach(function() {
            $scope.execution = {
                id: 254
            };
        });

        it("The execution's linked artifacts will be queried and attached to the execution", () => {
            const linked_issues = [
                { id: 554 },
                { id: 226 }
            ];
            spyOn(LinkedArtifactsService, "getAllLinkedIssues").and.callFake((
                execution,
                offset,
                progress_callback
            ) => {
                progress_callback(linked_issues);

                return $q.when();
            });

            $scope.refreshLinkedIssues();

            expect(LinkedArtifactsService.getAllLinkedIssues).toHaveBeenCalledWith($scope.execution, 0, jasmine.any(Function));
            expect($scope.execution.linked_bugs).toEqual(linked_issues);
        });

        it("When there is an error, it will be displayed on the execution", function() {
            spyOn(LinkedArtifactsService, "getAllLinkedIssues").and.returnValue($q.reject());
            spyOn(ExecutionService, "displayErrorMessage");

            $scope.refreshLinkedIssues();
            $scope.$apply();

            expect(ExecutionService.displayErrorMessage).toHaveBeenCalled();
        });
    });
});

//There is a circular dependency between campaign and execution
import ttm_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./campaign-edit-controller.js";

describe("CampaignEditController -", () => {
    let $scope,
        $ctrl,
        modal_instance,
        $q,
        SharedPropertiesService,
        CampaignService,
        DefinitionService,
        ExecutionService,
        NewTuleapArtifactModalService,
        editCampaignCallback,
        project_id,
        setError;

    beforeEach(() => {
        angular.mock.module(ttm_module);

        let $controller;

        angular.mock.inject(function(
            _$controller_,
            $rootScope,
            _$q_,
            _CampaignService_,
            _DefinitionService_,
            _ExecutionService_,
            _SharedPropertiesService_,
            _NewTuleapArtifactModalService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $scope = $rootScope.$new();
            CampaignService = _CampaignService_;
            ExecutionService = _ExecutionService_;
            DefinitionService = _DefinitionService_;
            SharedPropertiesService = _SharedPropertiesService_;
            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
        });

        modal_instance = {};
        editCampaignCallback = jasmine.createSpy("editCampaignCallback");

        project_id = 70;
        spyOn(SharedPropertiesService, "getProjectId").and.returnValue(project_id);
        spyOn(CampaignService, "getCampaign").and.returnValue($q.defer().promise);

        $ctrl = $controller(BaseController, {
            modal_instance,
            $scope,
            $q,
            SharedPropertiesService,
            CampaignService,
            DefinitionService,
            ExecutionService,
            NewTuleapArtifactModalService,
            editCampaignCallback
        });
        $ctrl.$onInit();
    });

    describe("selectReportTests() -", () => {
        beforeEach(() => {
            spyOn(DefinitionService, "getDefinitions");
        });

        it("Given a selected report, then the definitions of that report will be loaded and set to selected and all other tests will be unselected", () => {
            const definitions = [{ id: 85, summary: "AD test" }, { id: 3, summary: "Git test" }];
            DefinitionService.getDefinitions.and.returnValue($q.when(definitions));
            $scope.filters = {
                selected_report: "31"
            };
            $scope.tests_list = {
                uncategorized: {
                    tests: {
                        85: { definition: { id: 85, summary: "AD test" }, selected: false },
                        6: { definition: { id: 6, summary: "Other AD test", selected: true } }
                    }
                },
                git: {
                    tests: { 3: { definition: { id: 3, summary: "Git test", selected: false } } }
                }
            };

            $scope.selectReportTests();
            $scope.$apply();

            expect(DefinitionService.getDefinitions).toHaveBeenCalledWith(project_id, "31");
            expect($scope.tests_list.uncategorized.tests[85].selected).toBe(true);
            expect($scope.tests_list.uncategorized.tests[6].selected).toBe(false);
            expect($scope.tests_list.git.tests[3].selected).toBe(true);
        });

        it("Given no selected report, nothing happens", () => {
            $scope.filters = {
                selected_report: ""
            };

            $scope.selectReportTests();

            expect(DefinitionService.getDefinitions).not.toHaveBeenCalled();
        });
    });
});

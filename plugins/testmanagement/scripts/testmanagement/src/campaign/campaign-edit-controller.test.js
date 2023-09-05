/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

//There is a circular dependency between campaign and execution
import ttm_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./campaign-edit-controller.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

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
        wrapPromise;

    beforeEach(() => {
        angular.mock.module(ttm_module);

        let $controller, $httpBackend;

        angular.mock.inject(
            function (
                _$controller_,
                $rootScope,
                _$q_,
                _$httpBackend_,
                _CampaignService_,
                _DefinitionService_,
                _ExecutionService_,
                _SharedPropertiesService_,
                _NewTuleapArtifactModalService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $httpBackend = _$httpBackend_;
                $scope = $rootScope.$new();
                CampaignService = _CampaignService_;
                ExecutionService = _ExecutionService_;
                DefinitionService = _DefinitionService_;
                SharedPropertiesService = _SharedPropertiesService_;
                NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            },
        );

        wrapPromise = createAngularPromiseWrapper($scope);

        $httpBackend.when("GET", "campaign-list.tpl.html").respond(200);

        modal_instance = {};
        editCampaignCallback = jest.fn();

        project_id = 70;
        jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(project_id);
        jest.spyOn(CampaignService, "getCampaign").mockReturnValue($q.defer().promise);

        $ctrl = $controller(BaseController, {
            modal_instance,
            $scope,
            $q,
            SharedPropertiesService,
            CampaignService,
            DefinitionService,
            ExecutionService,
            NewTuleapArtifactModalService,
            editCampaignCallback,
        });
        $ctrl.$onInit();
    });

    describe("selectReportTests() -", () => {
        beforeEach(() => {
            jest.spyOn(DefinitionService, "getDefinitions").mockImplementation(() => {});
        });

        it("Given a selected report, then the definitions of that report will be loaded and set to selected and all other tests will be unselected", async () => {
            const definitions = [
                { id: 85, summary: "AD test" },
                { id: 3, summary: "Git test" },
            ];
            DefinitionService.getDefinitions.mockReturnValue($q.when(definitions));
            $scope.filters = {
                selected_report: "31",
            };
            $scope.tests_list = {
                uncategorized: {
                    tests: {
                        85: { definition: { id: 85, summary: "AD test" }, selected: false },
                        6: { definition: { id: 6, summary: "Other AD test", selected: true } },
                    },
                },
                git: {
                    tests: { 3: { definition: { id: 3, summary: "Git test", selected: false } } },
                },
            };

            await wrapPromise($scope.selectReportTests());

            expect(DefinitionService.getDefinitions).toHaveBeenCalledWith(project_id, "31");
            expect($scope.tests_list.uncategorized.tests[85].selected).toBe(true);
            expect($scope.tests_list.uncategorized.tests[6].selected).toBe(false);
            expect($scope.tests_list.git.tests[3].selected).toBe(true);
        });

        it("Given no selected report, nothing happens", () => {
            $scope.filters = {
                selected_report: "",
            };

            $scope.selectReportTests();

            expect(DefinitionService.getDefinitions).not.toHaveBeenCalled();
        });
    });
});

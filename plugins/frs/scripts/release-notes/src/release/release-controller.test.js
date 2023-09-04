/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import angular from "angular";
import tuleap_frs_module from "../app.js";
import BaseController from "./release-controller.js";

import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("ReleaseController", () => {
    let wrapPromise, $q, ReleaseController, ReleaseRestService, SharedPropertiesService;

    beforeEach(() => {
        let $controller, $rootScope;
        angular.mock.module(tuleap_frs_module);

        angular.mock.inject(
            function (
                _$q_,
                _$rootScope_,
                _$controller_,
                _ReleaseRestService_,
                _SharedPropertiesService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                ReleaseRestService = _ReleaseRestService_;
                SharedPropertiesService = _SharedPropertiesService_;
            },
        );

        jest.spyOn(SharedPropertiesService, "getProjectId").mockImplementation(() => {});
        jest.spyOn(SharedPropertiesService, "getRelease").mockImplementation(() => {});
        jest.spyOn(ReleaseRestService, "getMilestone").mockImplementation(() => {});

        ReleaseController = $controller(BaseController);

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("init()", () => {
        it(`Given that SharedPropertiesService had been correctly initialized,
            when I initialize the release controller
            then the release will be retrieved from the SharedPropertiesService
            and will be bound to the controller`, () => {
            const project_id = 150;
            const release = {
                id: 44,
                name: "v0.1.5 priceable-disconnectedness",
                package: {
                    label: "Axopodium",
                },
                project: {
                    id: project_id,
                },
                artifact: {
                    id: 230,
                },
            };

            SharedPropertiesService.getProjectId.mockReturnValue(project_id);
            SharedPropertiesService.getRelease.mockReturnValue(release);
            ReleaseRestService.getMilestone.mockReturnValue($q.when());

            ReleaseController.$onInit();

            expect(SharedPropertiesService.getProjectId).toHaveBeenCalled();
            expect(SharedPropertiesService.getRelease).toHaveBeenCalled();
            expect(ReleaseController.project_id).toEqual(project_id);
            expect(ReleaseController.release).toEqual(release);
            expect(ReleaseController.error_no_release_artifact).toBeFalsy();
        });

        it(`Given that no artifact had been bound to the FRS release,
            when I init the release controller
            then an error boolean will be set to true`, () => {
            const release = {
                id: 92,
                package: {
                    label: "Axopodium",
                },
                artifact: null,
            };
            SharedPropertiesService.getRelease.mockReturnValue(release);

            ReleaseController.$onInit();

            expect(ReleaseController.error_no_release_artifact).toBeTruthy();
        });

        it(`Given that no artifact had been bound to the FRS release,
            when I init the release controller
            then the milestone property is null`, () => {
            const release = {
                id: 92,
                package: {
                    label: "Axopodium",
                },
                artifact: null,
            };
            SharedPropertiesService.getRelease.mockReturnValue(release);

            ReleaseController.$onInit();

            expect(ReleaseController.milestone).toBeFalsy();
        });

        it(`Given that the artifact bound to the FRS release is also a milestone,
            when I init the release controller
            then the milestone property is fed with the Milestone object`, async () => {
            const release = {
                id: 44,
                package: {
                    label: "Axopodium",
                },
                artifact: {
                    id: 230,
                },
            };
            const milestone = {
                id: 230,
            };
            SharedPropertiesService.getRelease.mockReturnValue(release);
            const promise = $q.when(milestone);
            ReleaseRestService.getMilestone.mockReturnValue(promise);

            ReleaseController.$onInit();
            await wrapPromise(promise);

            expect(ReleaseController.milestone.id).toBe(230);
        });
    });
});

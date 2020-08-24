/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import testmanagment_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../tests/jest/angular-promise-wrapper.js";
import * as tlp from "tlp";

describe(`DefinitionService`, () => {
    let DefinitionService, mockBackend, wrapPromise, SharedPropertiesService;
    beforeEach(() => {
        angular.mock.module(testmanagment_module);
        let $rootScope;
        angular.mock.inject(function (
            _$rootScope_,
            $httpBackend,
            _DefinitionService_,
            _SharedPropertiesService_
        ) {
            $rootScope = _$rootScope_;
            mockBackend = $httpBackend;
            DefinitionService = _DefinitionService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);

        mockBackend.when("GET", "campaign-list.tpl.html").respond(200);
    });

    describe(`getDefinitionReports`, () => {
        it(`will call GET on tracker report`, () => {
            const report_id = 1;
            const artifact_id = 123;

            const tlpRecursiveGetSpy = jest
                .spyOn(tlp, "recursiveGet")
                .mockReturnValue(Promise.resolve(artifact_id));

            jest.spyOn(SharedPropertiesService, "getDefinitionTrackerId").mockReturnValue(
                report_id
            );

            DefinitionService.getDefinitionReports();

            expect(tlpRecursiveGetSpy).toHaveBeenCalledWith(
                `/api/v1/trackers/${report_id}/tracker_reports`,
                {
                    params: { limit: 10 },
                }
            );
        });
    });

    describe(`getArtifactById`, () => {
        it(`will call GET on artifacts and return the response`, async () => {
            const artifact_id = "123";
            mockBackend.expectGET("/api/v1/artifacts").respond(artifact_id);

            const promise = DefinitionService.getArtifactById();
            mockBackend.flush();
            const response = await wrapPromise(promise);

            expect(response).toEqual(artifact_id);
        });
    });

    describe(`getDefinitionById`, () => {
        it(`will call GET on artifacts and return the response`, async () => {
            const artifact_id = "123";
            mockBackend.expectGET("/api/v1/testmanagement_definitions").respond(artifact_id);

            const promise = DefinitionService.getDefinitionById();
            mockBackend.flush();
            const response = await wrapPromise(promise);

            expect(response).toEqual(artifact_id);
        });
    });

    describe(`getTracker`, () => {
        it(`will call GET on artifacts and return the response`, async () => {
            const artifact_id = "123";
            mockBackend.expectGET("/api/v1/trackers").respond(artifact_id);

            const promise = DefinitionService.getTracker();
            mockBackend.flush();
            const response = await wrapPromise(promise);

            expect(response).toEqual(artifact_id);
        });
    });
});

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
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import * as rest_querier from "../api/rest-querier";

describe(`DefinitionService`, () => {
    let DefinitionService, $q, wrapPromise, SharedPropertiesService;
    beforeEach(() => {
        angular.mock.module(testmanagment_module);
        let $rootScope, $httpBackend;
        angular.mock.inject(
            function (
                _$rootScope_,
                _$q_,
                _DefinitionService_,
                _SharedPropertiesService_,
                _$httpBackend_,
            ) {
                $rootScope = _$rootScope_;
                $q = _$q_;
                DefinitionService = _DefinitionService_;
                SharedPropertiesService = _SharedPropertiesService_;
                $httpBackend = _$httpBackend_;
            },
        );
        wrapPromise = createAngularPromiseWrapper($rootScope);
        $httpBackend.when("GET", "campaign-list.tpl.html").respond(200);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    describe(`getDefinitions`, () => {
        it(`will call recursiveGet on test definitions
            and will default their category to "Uncategorized"
            and will return the definitions`, async () => {
            const categorized_definition = {
                id: 967,
                category: "Trackers",
            };
            const uncategorized_definition = { id: 675 };
            jest.spyOn(rest_querier, "getDefinitions").mockReturnValue(
                $q.when([categorized_definition, uncategorized_definition]),
            );

            const report_id = 31;
            const project_id = 7;
            const promise = DefinitionService.getDefinitions(project_id, report_id);
            const result = await wrapPromise(promise);

            expect(result).toContain(categorized_definition);
            expect(result).toContainEqual({ id: 675, category: "Uncategorized" });
        });
    });

    describe(`getDefinitionReports`, () => {
        it(`will call GET on tracker report`, async () => {
            const report_id = 1;
            const artifact_id = 123;

            const tlpRecursiveGetSpy = jest
                .spyOn(tlp_fetch, "recursiveGet")
                .mockReturnValue($q.when([{ id: artifact_id }]));

            jest.spyOn(SharedPropertiesService, "getDefinitionTrackerId").mockReturnValue(
                report_id,
            );

            const promise = DefinitionService.getDefinitionReports();
            await wrapPromise(promise);

            expect(tlpRecursiveGetSpy).toHaveBeenCalledWith(
                `/api/v1/trackers/${report_id}/tracker_reports`,
                {
                    params: { limit: 10 },
                },
            );
        });
    });

    describe(`getArtifactById`, () => {
        it(`will call GET on artifacts and return the response`, async () => {
            const artifact = { id: 123 };
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: artifact });

            const promise = DefinitionService.getArtifactById(123);
            const response = await wrapPromise(promise);

            expect(response).toEqual(artifact);
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/artifacts/123");
        });
    });

    describe(`getDefinitionById`, () => {
        it(`will call GET on testmanagement_definitions and return the response`, async () => {
            const definition = { id: 123 };
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: definition });

            const promise = DefinitionService.getDefinitionById(123);
            const response = await wrapPromise(promise);

            expect(response).toEqual(definition);
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/testmanagement_definitions/123");
        });
    });

    describe(`getTracker`, () => {
        it(`will call GET on trackers and return the response`, async () => {
            const tracker = { id: 12 };
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: tracker });

            const promise = DefinitionService.getTracker(12);
            const response = await wrapPromise(promise);

            expect(response).toEqual(tracker);
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/trackers/12");
        });
    });
});

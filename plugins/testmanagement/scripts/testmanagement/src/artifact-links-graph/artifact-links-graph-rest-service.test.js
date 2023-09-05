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
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe(`ArtifactLinksGraphRestService`, () => {
    let ArtifactLinksGraphRestService, $q, wrapPromise;
    beforeEach(() => {
        angular.mock.module(testmanagment_module);
        let $rootScope, mockBackend;
        angular.mock.inject(
            function (_$rootScope_, _$q_, $httpBackend, _ArtifactLinksGraphRestService_) {
                $rootScope = _$rootScope_;
                $q = _$q_;
                mockBackend = $httpBackend;
                ArtifactLinksGraphRestService = _ArtifactLinksGraphRestService_;
            },
        );

        wrapPromise = createAngularPromiseWrapper($rootScope);

        mockBackend.when("GET", "campaign-list.tpl.html").respond(200);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    describe(`getArtifactGraph`, () => {
        it(`will call GET on test management nodes and return the response`, async () => {
            const links_representation = {
                id: 456,
                title: "mobilizable",
                color: "inca-silver",
                links: [{ id: 123, title: "metallography", color: "inca-silver" }],
            };
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: links_representation });

            const promise = ArtifactLinksGraphRestService.getArtifactGraph(25);
            const response = await wrapPromise(promise);

            expect(response).toEqual(links_representation);
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/testmanagement_nodes/25");
        });
    });
});

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

describe(`ArtifactLinksGraphRestService`, () => {
    let ArtifactLinksGraphRestService, mockBackend, wrapPromise;
    beforeEach(() => {
        angular.mock.module(testmanagment_module);
        let $rootScope;
        angular.mock.inject(function (_$rootScope_, $httpBackend, _ArtifactLinksGraphRestService_) {
            $rootScope = _$rootScope_;
            mockBackend = $httpBackend;
            ArtifactLinksGraphRestService = _ArtifactLinksGraphRestService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);

        mockBackend.when("GET", "campaign-list.tpl.html").respond(200);
    });

    describe(`getArtifactGraph`, () => {
        it(`will call GET on test management nodes and return the response`, async () => {
            const artifact_id = "123";
            mockBackend.expectGET("/api/v1/testmanagement_nodes").respond(artifact_id);

            const promise = ArtifactLinksGraphRestService.getArtifactGraph();
            mockBackend.flush();
            const response = await wrapPromise(promise);

            expect(response).toEqual(artifact_id);
        });
    });
});

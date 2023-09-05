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

describe(`JWTService`, () => {
    let JWTService, $q, wrapPromise;
    beforeEach(() => {
        angular.mock.module(testmanagment_module);
        let $rootScope, mockBackend;
        angular.mock.inject(function (_$rootScope_, _$q_, $httpBackend, _JWTService_) {
            $rootScope = _$rootScope_;
            $q = _$q_;
            mockBackend = $httpBackend;
            JWTService = _JWTService_;
        });

        mockBackend.when("GET", "campaign-list.tpl.html").respond(200);

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    describe(`getJWT`, () => {
        it(`will call GET on jwt and return the response`, async () => {
            const token = "aaaaa";
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: token });

            const promise = JWTService.getJWT();
            const response = await wrapPromise(promise);

            expect(response).toEqual(token);
            expect(tlpGet).toHaveBeenCalledWith("/api/v1/jwt");
        });
    });
});

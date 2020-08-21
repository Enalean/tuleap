/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper.js";

describe(`JWTService`, () => {
    let JWTService, mockBackend, wrapPromise;
    beforeEach(() => {
        angular.mock.module(kanban_module);
        let $rootScope;
        angular.mock.inject(function (_$rootScope_, $httpBackend, _JWTService_) {
            $rootScope = _$rootScope_;
            mockBackend = $httpBackend;
            JWTService = _JWTService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe(`getJWT`, () => {
        it(`will call GET on jwt and return the response`, async () => {
            const token = "aaaaa";
            mockBackend.expectGET("/api/v1/jwt").respond(token);

            const promise = JWTService.getJWT();
            mockBackend.flush();
            const response = await wrapPromise(promise);

            expect(response).toEqual(token);
        });
    });
});

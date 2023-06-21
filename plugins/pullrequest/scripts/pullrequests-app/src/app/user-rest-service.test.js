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
import tuleap_pullrequest_module from "./app.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import "angular-mocks";

describe("UserRestService -", function () {
    let $httpBackend, UserRestService, ErrorModalService, wrapPromise;

    beforeEach(function () {
        let $rootScope;
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (
            _$rootScope_,
            _$httpBackend_,
            _UserRestService_,
            _ErrorModalService_
        ) {
            $rootScope = _$rootScope_;
            $httpBackend = _$httpBackend_;
            UserRestService = _UserRestService_;
            ErrorModalService = _ErrorModalService_;
        });

        jest.spyOn(ErrorModalService, "showErrorResponseMessage").mockImplementation(() => {});

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(function () {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getUser()", function () {
        it("Given an user id, when I get it, then a GET request will be sent to Tuleap and a promise will be resolved with a user object", async function () {
            const user_id = 123;

            const user = {
                id: user_id,
                display_name: "Susanne Tickle (stickle)",
                avatar_url:
                    "https://mazalgia.com/semicurl/epitonic?a=pneumomalacia&b=superfinical#receptaculitid",
                user_url:
                    "http://mucinoid.com/freely/prestudy?a=hauranitic&b=summerproof#roadtrack",
            };

            $httpBackend.expectGET("/api/v1/users/" + user_id).respond(angular.toJson(user));

            const promise = wrapPromise(UserRestService.getUser(user_id));
            $httpBackend.flush();

            await expect(promise).resolves.toStrictEqual(user);
        });

        it("when the server responds with an error, then the error modal will be shown", async function () {
            const user_id = 12;

            $httpBackend.expectGET("/api/v1/users/" + user_id).respond(403, "Forbidden");

            const promise = wrapPromise(UserRestService.getUser(user_id));
            $httpBackend.flush();

            await expect(promise).rejects.toMatchObject({
                status: 403,
            });
            expect(ErrorModalService.showErrorResponseMessage).toHaveBeenCalledWith(
                expect.objectContaining({
                    status: 403,
                    statusText: "",
                })
            );
        });
    });

    describe("getPreference()", () => {
        it("Given an user id and an user preference, when I get it, then a GET request will be sent to Tuleap and a promise will be resolved with a user object", async () => {
            const user_id = 123;

            const preference = {
                key: "preferred_color",
                value: "red",
            };

            $httpBackend
                .expectGET("/api/v1/users/123/preferences?key=preferred_color")
                .respond(angular.toJson(preference));

            const promise = wrapPromise(UserRestService.getPreference(user_id, "preferred_color"));
            $httpBackend.flush();

            await expect(promise).resolves.toStrictEqual(preference);
        });

        it("when the server responds with an error, then the error modal will be shown", async () => {
            const user_id = 12;

            $httpBackend
                .expectGET("/api/v1/users/12/preferences?key=preferred_color")
                .respond(403, "Forbidden");

            const promise = wrapPromise(UserRestService.getPreference(user_id, "preferred_color"));
            $httpBackend.flush();

            await expect(promise).rejects.toMatchObject({
                status: 403,
            });
            expect(ErrorModalService.showErrorResponseMessage).toHaveBeenCalledWith(
                expect.objectContaining({
                    status: 403,
                    statusText: "",
                })
            );
        });
    });

    describe("setPreference", () => {
        it("Given an user id, an user_preference and a value, When it set it, Then a PATCH request will be sent to Tuleap.", async () => {
            const user_id = 666;

            $httpBackend
                .expectPATCH("/api/v1/users/666/preferences", {
                    key: "religion",
                    value: "Satan",
                })
                .respond(200, "ok");

            const promise = wrapPromise(
                UserRestService.setPreference(user_id, "religion", "Satan")
            );
            $httpBackend.flush();

            await expect(promise).resolves.toBeDefined();
        });
    });
});

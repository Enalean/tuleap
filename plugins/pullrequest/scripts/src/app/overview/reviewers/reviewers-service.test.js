/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import tuleap_pullrequest_module from "../../app.js";

import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper.js";

describe("ReviewersService", () => {
    let $rootScope, $httpBackend, ReviewersService, TlpModalService, wrapPromise, pull_request;

    beforeEach(() => {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (
            _$rootScope_,
            _$httpBackend_,
            _ReviewersService_,
            _TlpModalService_
        ) {
            $rootScope = _$rootScope_;
            $httpBackend = _$httpBackend_;
            ReviewersService = _ReviewersService_;
            TlpModalService = _TlpModalService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);

        pull_request = {
            id: 1,
        };
    });

    describe("getReviewers", () => {
        let expected_url;

        beforeEach(() => {
            expected_url = "/api/v1/pull_requests/" + pull_request.id + "/reviewers";
        });

        it("Given a pull request with no reviewer, then it will return an empty array", async () => {
            $httpBackend.expectGET(expected_url).respond({
                users: [],
            });

            const promise = wrapPromise(ReviewersService.getReviewers(pull_request));
            $httpBackend.flush();

            expect(await promise).toEqual([]);
        });

        it("Given a pull request with 2 reviewers, then it will return an array with 2 users", async () => {
            $httpBackend.expectGET(expected_url).respond({
                users: [
                    {
                        id: 1,
                        username: "lorem",
                    },
                    {
                        id: 2,
                        username: "ipsum",
                    },
                ],
            });

            const promise = wrapPromise(ReviewersService.getReviewers(pull_request));
            $httpBackend.flush();

            const result = await promise;

            expect(result.length).toEqual(2);
            expect(result[1].username).toEqual("ipsum");
        });
    });

    describe("buildUserRepresentationsForPut", () => {
        it("Given an array of user representations, then it will return an array of PUT user representations based on user ids", () => {
            const user_representations = [
                {
                    avatar_url: "https://example.com/avatar.png",
                    display_name: "Bob Lemoche (bob)",
                    has_avatar: true,
                    id: 102,
                    is_anonymous: false,
                    ldap_id: "102",
                    real_name: "Bob Lemoche",
                    uri: "users/102",
                    user_url: "/users/bob",
                    username: "bob",
                },
                {
                    avatar_url: "https://example.com/avatar.png",
                    display_name: "Woody Bacon (woody)",
                    email: "woody@test",
                    has_avatar: false,
                    id: 103,
                    is_anonymous: false,
                    ldap_id: "103",
                    real_name: "Woody Bacon",
                    selected: true,
                    status: "A",
                    uri: "users/103",
                    user_url: "/users/woody",
                    username: "woody",
                },
            ];

            const small_representations = ReviewersService.buildUserRepresentationsForPut(
                user_representations
            );
            expect(small_representations).toEqual([{ id: 102 }, { id: 103 }]);
        });
    });

    describe("updateReviewers", () => {
        let expected_url, user_representations;

        beforeEach(() => {
            expected_url = "/api/v1/pull_requests/" + pull_request.id + "/reviewers";
            user_representations = [{ id: 102 }, { id: 103 }];
        });

        it("Give a pull request and user representations with merge permissions, then it will call a REST route and return a 204 status", async () => {
            $httpBackend
                .expectPUT(expected_url, {
                    users: user_representations,
                })
                .respond(204);

            const promise = ReviewersService.updateReviewers(pull_request, user_representations);
            $httpBackend.flush();

            const result = await wrapPromise(promise);
            expect(result.status).toEqual(204);
        });

        it("Give a pull request and user representations with wrong permissions, then it will call a REST route and open error modal", async () => {
            jest.spyOn(TlpModalService, "open").mockImplementation(() => {});

            $httpBackend.expectPUT(expected_url, { users: user_representations }).respond(400, {
                error: "Nope",
            });

            const promise = ReviewersService.updateReviewers(pull_request, user_representations);
            $httpBackend.flush();

            await wrapPromise(promise);
            expect(TlpModalService.open).toHaveBeenCalled();
        });
    });
});

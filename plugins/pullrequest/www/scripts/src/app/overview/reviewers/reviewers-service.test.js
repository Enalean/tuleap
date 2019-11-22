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
import { createAngularPromiseWrapper } from "../../../../../../../../tests/jest/angular-promise-wrapper.js";

describe("ReviewersService", () => {
    let $rootScope, $httpBackend, ReviewersService, wrapPromise, pull_request, expected_url;

    beforeEach(function() {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function(_$rootScope_, _$httpBackend_, _ReviewersService_) {
            $rootScope = _$rootScope_;
            $httpBackend = _$httpBackend_;
            ReviewersService = _ReviewersService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);

        pull_request = {
            id: 1
        };
        expected_url = "/api/v1/pull_requests/" + pull_request.id + "/reviewers";
    });

    it("Given a pull request with no reviewer, then it will return an empty array", async () => {
        $httpBackend.expectGET(expected_url).respond({
            users: []
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
                    username: "lorem"
                },
                {
                    id: 2,
                    username: "ipsum"
                }
            ]
        });

        const promise = wrapPromise(ReviewersService.getReviewers(pull_request));
        $httpBackend.flush();

        const result = await promise;

        expect(result.length).toEqual(2);
        expect(result[1].username).toEqual("ipsum");
    });
});

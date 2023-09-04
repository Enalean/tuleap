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
import tuleap_pullrequest_module from "../app.js";

import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("PullRequestRestService -", function () {
    var $httpBackend, PullRequestRestService, ErrorModalService, wrapPromise;

    beforeEach(function () {
        let $rootScope;
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(
            function (_$rootScope_, _$httpBackend_, _ErrorModalService_, _PullRequestRestService_) {
                $rootScope = _$rootScope_;
                $httpBackend = _$httpBackend_;
                ErrorModalService = _ErrorModalService_;
                PullRequestRestService = _PullRequestRestService_;
            },
        );

        jest.spyOn(ErrorModalService, "showErrorResponseMessage").mockImplementation(() => {});

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(function () {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getPullRequest()", function () {
        it("Given a pull_request id, when I get it, then a GET request will be sent to Tuleap and a promise will be resolved with a pull_request object", async function () {
            var pull_request_id = 83;

            var pull_request = {
                id: pull_request_id,
                title: "Asking a PR",
                user_id: 101,
                branch_src: "sample-pr",
                branch_dest: "master",
                repository: {
                    id: 1,
                },
                repository_dest: {
                    id: 2,
                },
                status: "review",
                creation_date: "2016-04-19T09:20:21+00:00",
            };

            $httpBackend
                .expectGET("/api/v1/pull_requests/" + pull_request_id)
                .respond(angular.toJson(pull_request));

            var promise = wrapPromise(PullRequestRestService.getPullRequest(pull_request_id));
            $httpBackend.flush();

            await expect(promise).resolves.toStrictEqual(pull_request);
        });

        it("when the server responds with an error, then the error modal will be shown", async function () {
            var pull_request_id = 48;

            $httpBackend
                .expectGET("/api/v1/pull_requests/" + pull_request_id)
                .respond(403, "Forbidden");

            var promise = wrapPromise(PullRequestRestService.getPullRequest(pull_request_id));
            $httpBackend.flush();

            await expect(promise).rejects.toMatchObject({
                status: 403,
            });
            expect(ErrorModalService.showErrorResponseMessage).toHaveBeenCalledWith(
                expect.objectContaining({
                    status: 403,
                    statusText: "",
                }),
            );
        });
    });
});

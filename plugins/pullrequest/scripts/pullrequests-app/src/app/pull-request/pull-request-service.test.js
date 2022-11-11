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

describe("PullRequestService -", function () {
    var PullRequestService;

    beforeEach(function () {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (_PullRequestService_) {
            PullRequestService = _PullRequestService_;
        });
    });

    describe("isPullRequestClosed()", function () {
        it("Given a pull request with the 'merge' status, then it will return true", function () {
            var pull_request = {
                status: "merge",
            };

            var result = PullRequestService.isPullRequestClosed(pull_request);

            expect(result).toBe(true);
        });

        it("Given a pull request with the 'abandon' status, then it will return true", function () {
            var pull_request = {
                status: "abandon",
            };

            var result = PullRequestService.isPullRequestClosed(pull_request);

            expect(result).toBe(true);
        });

        it("Given a pull request with the 'review' status, then it will return false", function () {
            var pull_request = {
                status: "review",
            };

            var result = PullRequestService.isPullRequestClosed(pull_request);

            expect(result).toBe(false);
        });
    });
});

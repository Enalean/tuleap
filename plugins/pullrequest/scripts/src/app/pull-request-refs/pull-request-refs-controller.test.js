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
import tuleap_pullrequest_module from "../../app/app.js";
import pullrequest_refs_controller from "./pull-request-refs-controller.js";

import "angular-mocks";

describe("PullRequestRefsController -", function () {
    let SharedPropertiesService, PullRequestRefsController;

    beforeEach(function () {
        let $controller;

        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (_$controller_, _SharedPropertiesService_) {
            $controller = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        PullRequestRefsController = $controller(pullrequest_refs_controller, {
            SharedPropertiesService: SharedPropertiesService,
        });

        jest.spyOn(SharedPropertiesService, "getRepositoryId").mockImplementation(() => {});
    });

    describe("isCurrentRepository()", function () {
        it("Given the current repository id in SharedPropertiesService and given a repository object with the same id, when I check if it is the current repository, then it will return true", function () {
            SharedPropertiesService.getRepositoryId.mockReturnValue(14);

            const repository = {
                id: 14,
            };

            const result = PullRequestRefsController.isCurrentRepository(repository);

            expect(result).toBe(true);
        });

        it("Given no repository, when I check if it is the current repository, then it will return false", function () {
            SharedPropertiesService.getRepositoryId.mockReturnValue(14);

            const result = PullRequestRefsController.isCurrentRepository();

            expect(result).toBe(false);
        });
    });

    describe("isRepositoryAFork()", function () {
        it("Given a pull request with a repository id different of its repository_dest id, when I check if the repository is a fork, then it will return true", function () {
            PullRequestRefsController.pull_request = {
                repository: {
                    id: 22,
                },
                repository_dest: {
                    id: 61,
                },
            };

            const result = PullRequestRefsController.isRepositoryAFork();

            expect(result).toBe(true);
        });

        it("Given a pull request with no repository, when I check if the repository is a fork, then it will return false", function () {
            PullRequestRefsController.pull_request = {};

            const result = PullRequestRefsController.isRepositoryAFork();

            expect(result).toBe(false);
        });
    });
});

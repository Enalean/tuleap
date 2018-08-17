import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";
import pullrequest_refs_controller from "./pull-request-refs-controller.js";

import "angular-mocks";

describe("PullRequestRefsController -", function() {
    let SharedPropertiesService, PullRequestRefsController;

    beforeEach(function() {
        let $controller;

        angular.mock.module(tuleap_pullrequest_module);

        // eslint-disable-next-line angular/di
        angular.mock.inject(function(_$controller_, _SharedPropertiesService_) {
            $controller = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        PullRequestRefsController = $controller(pullrequest_refs_controller, {
            SharedPropertiesService: SharedPropertiesService
        });

        spyOn(SharedPropertiesService, "getRepositoryId");
    });

    describe("isCurrentRepository()", function() {
        it("Given the current repository id in SharedPropertiesService and given a repository object with the same id, when I check if it is the current repository, then it will return true", function() {
            SharedPropertiesService.getRepositoryId.and.returnValue(14);

            const repository = {
                id: 14
            };

            const result = PullRequestRefsController.isCurrentRepository(repository);

            expect(result).toBe(true);
        });

        it("Given no repository, when I check if it is the current repository, then it will return false", function() {
            SharedPropertiesService.getRepositoryId.and.returnValue(14);

            const result = PullRequestRefsController.isCurrentRepository();

            expect(result).toBe(false);
        });
    });

    describe("isRepositoryAFork()", function() {
        it("Given a pull request with a repository id different of its repository_dest id, when I check if the repository is a fork, then it will return true", function() {
            PullRequestRefsController.pull_request = {
                repository: {
                    id: 22
                },
                repository_dest: {
                    id: 61
                }
            };

            const result = PullRequestRefsController.isRepositoryAFork();

            expect(result).toBe(true);
        });

        it("Given a pull request with no repository, when I check if the repository is a fork, then it will return false", function() {
            PullRequestRefsController.pull_request = {};

            const result = PullRequestRefsController.isRepositoryAFork();

            expect(result).toBe(false);
        });
    });
});

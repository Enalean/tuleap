import angular from "angular";
import tuleap_pullrequest_module from "../app.js";

import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("PullRequestCollectionService -", function () {
    let $q,
        PullRequestCollectionService,
        SharedPropertiesService,
        PullRequestCollectionRestService,
        wrapPromise;

    beforeEach(function () {
        let $rootScope;
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(
            function (
                _$rootScope_,
                _$q_,
                _PullRequestCollectionRestService_,
                _PullRequestCollectionService_,
                _SharedPropertiesService_,
            ) {
                $rootScope = _$rootScope_;
                $q = _$q_;
                PullRequestCollectionRestService = _PullRequestCollectionRestService_;
                PullRequestCollectionService = _PullRequestCollectionService_;
                SharedPropertiesService = _SharedPropertiesService_;
            },
        );

        jest.spyOn(PullRequestCollectionRestService, "getAllPullRequests").mockImplementation(
            () => {},
        );
        jest.spyOn(PullRequestCollectionRestService, "getAllOpenPullRequests").mockImplementation(
            () => {},
        );
        jest.spyOn(PullRequestCollectionRestService, "getAllClosedPullRequests").mockImplementation(
            () => {},
        );
        jest.spyOn(SharedPropertiesService, "getRepositoryId").mockImplementation(() => {});

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("loadAllPullRequests()", function () {
        beforeEach(function () {
            SharedPropertiesService.getRepositoryId.mockReturnValue(1);
        });

        it("When I load all pull requests, then the REST service will be called, the open pull requests will be stored before the closed pull requests", async function () {
            const pull_requests = [
                { id: 1, status: "merge" },
                { id: 2, status: "review" },
                { id: 3, status: "abandon" },
                { id: 4, status: "review" },
            ];
            PullRequestCollectionRestService.getAllPullRequests.mockReturnValue(
                $q.when(pull_requests),
            );

            const promise = wrapPromise(PullRequestCollectionService.loadAllPullRequests());

            const expected_pull_requests = [
                { id: 2, status: "review" },
                { id: 4, status: "review" },
                { id: 1, status: "merge" },
                { id: 3, status: "abandon" },
            ];

            await promise;
            expect(PullRequestCollectionService.all_pull_requests).toStrictEqual(
                expected_pull_requests,
            );
            expect(PullRequestCollectionService.areAllPullRequestsFullyLoaded()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneClosedPullRequest()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneOpenpullRequest()).toBe(true);
        });

        it("Given that pull requests had already been loaded once, when I load all pull requests again, then the stored pull requests will be emptied and stored again", async function () {
            const all_pr_reference = (PullRequestCollectionService.all_pull_requests = [
                { id: 1, status: "merge" },
                { id: 2, status: "review" },
            ]);

            const updated_pull_requests = [
                { id: 1, status: "merge" },
                { id: 2, status: "merge" },
                { id: 3, status: "abandon" },
                { id: 4, status: "review" },
            ];
            PullRequestCollectionRestService.getAllPullRequests.mockReturnValue(
                $q.when(updated_pull_requests),
            );

            const promise = wrapPromise(PullRequestCollectionService.loadAllPullRequests());

            await promise;
            expect(PullRequestCollectionService.all_pull_requests).toBe(all_pr_reference);
            expect(PullRequestCollectionService.all_pull_requests).toStrictEqual([
                { id: 4, status: "review" },
                { id: 1, status: "merge" },
                { id: 2, status: "merge" },
                { id: 3, status: "abandon" },
            ]);
        });
    });

    describe("loadOpenPullRequests()", function () {
        beforeEach(function () {
            SharedPropertiesService.getRepositoryId.mockReturnValue(5);
        });

        it("When I load open pull requests, then the REST service will be called and the open pull requests will be progressively loaded", async function () {
            const open_pull_requests = [
                { id: 1, status: "review" },
                { id: 2, status: "review" },
            ];
            PullRequestCollectionRestService.getAllOpenPullRequests.mockImplementation(
                function (repository_id, callback) {
                    callback(open_pull_requests);
                    return $q.when(open_pull_requests);
                },
            );

            const promise = wrapPromise(PullRequestCollectionService.loadOpenPullRequests());

            await promise;
            expect(PullRequestCollectionService.all_pull_requests).toStrictEqual(
                open_pull_requests,
            );
            expect(PullRequestCollectionService.areOpenPullRequestsFullyLoaded()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneOpenpullRequest()).toBe(true);
        });

        it("Given that all open pull requests had already been loaded once but closed pull requests had not been loaded, when I load the open pull requests again, then the stored pull requests will be emptied and stored again", async function () {
            jest.spyOn(
                PullRequestCollectionService,
                "areOpenPullRequestsFullyLoaded",
            ).mockReturnValue(true);
            jest.spyOn(
                PullRequestCollectionService,
                "areClosedPullRequestsFullyLoaded",
            ).mockReturnValue(false);

            const all_pr_reference = (PullRequestCollectionService.all_pull_requests = [
                { id: 1, status: "review" },
                { id: 2, status: "review" },
            ]);

            const updated_pull_requests = [
                { id: 3, status: "review" },
                { id: 4, status: "review" },
            ];
            PullRequestCollectionRestService.getAllOpenPullRequests.mockReturnValue(
                $q.when(updated_pull_requests),
            );

            const promise = wrapPromise(PullRequestCollectionService.loadOpenPullRequests());

            await promise;
            expect(PullRequestCollectionService.all_pull_requests).toBe(all_pr_reference);
            expect(PullRequestCollectionService.all_pull_requests).toStrictEqual([
                { id: 3, status: "review" },
                { id: 4, status: "review" },
            ]);
        });
    });

    describe("loadClosedPullRequests()", function () {
        beforeEach(function () {
            SharedPropertiesService.getRepositoryId.mockReturnValue(3);
        });

        it("When I load closed pull requests, then the REST service will be called and the closed pull requests will be progressively loaded and added to all the pull requests", async function () {
            const all_pr_reference = (PullRequestCollectionService.all_pull_requests = [
                { id: 2, status: "review" },
                { id: 1, status: "review" },
            ]);

            const closed_pull_requests = [
                { id: 3, status: "merge" },
                { id: 4, status: "abandon" },
            ];
            PullRequestCollectionRestService.getAllClosedPullRequests.mockImplementation(
                function (repository_id, callback) {
                    callback(closed_pull_requests);
                    return $q.when(closed_pull_requests);
                },
            );

            const promise = wrapPromise(PullRequestCollectionService.loadClosedPullRequests());

            const expected_pull_requests = [
                { id: 2, status: "review" },
                { id: 1, status: "review" },
                { id: 3, status: "merge" },
                { id: 4, status: "abandon" },
            ];

            await promise;
            expect(PullRequestCollectionService.all_pull_requests).toBe(all_pr_reference);
            expect(PullRequestCollectionService.all_pull_requests).toStrictEqual(
                expected_pull_requests,
            );
            expect(PullRequestCollectionService.areClosedPullRequestsFullyLoaded()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneClosedPullRequest()).toBe(true);
        });
    });

    describe("search()", function () {
        it("Given a pull request id stored in all the pull requests, when I search for it, then it will be returned", function () {
            const pull_request = {
                id: 75,
            };
            PullRequestCollectionService.all_pull_requests = [pull_request];

            const result = PullRequestCollectionService.search(75);

            expect(result).toBe(pull_request);
        });

        it("Given a pull request that was not stored in all the pull requests, when I search for it, then undefined will be returned", function () {
            const pull_request = {
                id: 98,
            };
            PullRequestCollectionService.all_pull_requests = [pull_request];

            const result = PullRequestCollectionService.search(11);

            expect(result).toBeUndefined();
        });
    });
});

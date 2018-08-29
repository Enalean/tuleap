import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";

import "angular-mocks";

describe("PullRequestCollectionService -", function() {
    let $q, PullRequestCollectionService, SharedPropertiesService, PullRequestCollectionRestService;

    beforeEach(function() {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function(
            _$q_,
            _PullRequestCollectionRestService_,
            _PullRequestCollectionService_,
            _SharedPropertiesService_
        ) {
            $q = _$q_;
            PullRequestCollectionRestService = _PullRequestCollectionRestService_;
            PullRequestCollectionService = _PullRequestCollectionService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(PullRequestCollectionRestService, "getAllPullRequests");
        spyOn(PullRequestCollectionRestService, "getAllOpenPullRequests");
        spyOn(PullRequestCollectionRestService, "getAllClosedPullRequests");
        spyOn(SharedPropertiesService, "getRepositoryId");

        installPromiseMatchers();
    });

    describe("loadAllPullRequests()", function() {
        beforeEach(function() {
            SharedPropertiesService.getRepositoryId.and.returnValue(1);
        });

        it("When I load all pull requests, then the REST service will be called, the open pull requests will be stored before the closed pull requests and all pull requests will be stored by reverse order of creation date", function() {
            const pull_requests = [
                { id: 1, status: "merge" },
                { id: 2, status: "review" },
                { id: 3, status: "abandon" },
                { id: 4, status: "review" }
            ];
            PullRequestCollectionRestService.getAllPullRequests.and.returnValue(
                $q.when(pull_requests)
            );

            const promise = PullRequestCollectionService.loadAllPullRequests();

            const reversed_and_ordered_pull_requests = [
                { id: 4, status: "review" },
                { id: 2, status: "review" },
                { id: 3, status: "abandon" },
                { id: 1, status: "merge" }
            ];

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toEqual(
                reversed_and_ordered_pull_requests
            );
            expect(PullRequestCollectionService.areAllPullRequestsFullyLoaded()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneClosedPullRequest()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneOpenpullRequest()).toBe(true);
        });

        it("Given that pull requests had already been loaded once, when I load all pull requests again, then the stored pull requests will be emptied and stored again", function() {
            const all_pr_reference = (PullRequestCollectionService.all_pull_requests = [
                { id: 1, status: "merge" },
                { id: 2, status: "review" }
            ]);

            const updated_pull_requests = [
                { id: 1, status: "merge" },
                { id: 2, status: "merge" },
                { id: 3, status: "abandon" },
                { id: 4, status: "review" }
            ];
            PullRequestCollectionRestService.getAllPullRequests.and.returnValue(
                $q.when(updated_pull_requests)
            );

            const promise = PullRequestCollectionService.loadAllPullRequests();

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toBe(all_pr_reference);
            expect(PullRequestCollectionService.all_pull_requests).toEqual([
                { id: 4, status: "review" },
                { id: 3, status: "abandon" },
                { id: 2, status: "merge" },
                { id: 1, status: "merge" }
            ]);
        });
    });

    describe("loadOpenPullRequests()", function() {
        beforeEach(function() {
            SharedPropertiesService.getRepositoryId.and.returnValue(5);
        });

        it("When I load open pull requests, then the REST service will be called and the open pull requests will be progressively loaded", function() {
            const open_pull_requests = [{ id: 1, status: "review" }, { id: 2, status: "review" }];
            PullRequestCollectionRestService.getAllOpenPullRequests.and.callFake(function(
                repository_id,
                callback
            ) {
                callback(open_pull_requests);
                return $q.when(open_pull_requests);
            });

            const promise = PullRequestCollectionService.loadOpenPullRequests();

            const reversed_pull_requests = [
                { id: 2, status: "review" },
                { id: 1, status: "review" }
            ];

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toEqual(reversed_pull_requests);
            expect(PullRequestCollectionService.areOpenPullRequestsFullyLoaded()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneOpenpullRequest()).toBe(true);
        });

        it("Given that all open pull requests had already been loaded once but closed pull requests had not been loaded, when I load the open pull requests again, then the stored pull requests will be emptied and stored again", function() {
            spyOn(PullRequestCollectionService, "areOpenPullRequestsFullyLoaded").and.returnValue(
                true
            );
            spyOn(PullRequestCollectionService, "areClosedPullRequestsFullyLoaded").and.returnValue(
                false
            );

            const all_pr_reference = (PullRequestCollectionService.all_pull_requests = [
                { id: 1, status: "review" },
                { id: 2, status: "review" }
            ]);

            const updated_pull_requests = [
                { id: 3, status: "review" },
                { id: 4, status: "review" }
            ];
            PullRequestCollectionRestService.getAllOpenPullRequests.and.returnValue(
                $q.when(updated_pull_requests)
            );

            const promise = PullRequestCollectionService.loadOpenPullRequests();

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toBe(all_pr_reference);
            expect(PullRequestCollectionService.all_pull_requests).toEqual([
                { id: 4, status: "review" },
                { id: 3, status: "review" }
            ]);
        });
    });

    describe("loadClosedPullRequests()", function() {
        beforeEach(function() {
            SharedPropertiesService.getRepositoryId.and.returnValue(3);
        });

        it("When I load closed pull requests, then the REST service will be called and the closed pull requests will be progressively loaded and added to all the pull requests", function() {
            const all_pr_reference = (PullRequestCollectionService.all_pull_requests = [
                { id: 2, status: "review" },
                { id: 1, status: "review" }
            ]);

            const closed_pull_requests = [{ id: 3, status: "merge" }, { id: 4, status: "abandon" }];
            PullRequestCollectionRestService.getAllClosedPullRequests.and.callFake(function(
                repository_id,
                callback
            ) {
                callback(closed_pull_requests);
                return $q.when(closed_pull_requests);
            });

            const promise = PullRequestCollectionService.loadClosedPullRequests();

            const reversed_pull_requests = [
                { id: 2, status: "review" },
                { id: 1, status: "review" },
                { id: 4, status: "abandon" },
                { id: 3, status: "merge" }
            ];

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toBe(all_pr_reference);
            expect(PullRequestCollectionService.all_pull_requests).toEqual(reversed_pull_requests);
            expect(PullRequestCollectionService.areClosedPullRequestsFullyLoaded()).toBe(true);
            expect(PullRequestCollectionService.isThereAtLeastOneClosedPullRequest()).toBe(true);
        });
    });

    describe("search()", function() {
        it("Given a pull request id stored in all the pull requests, when I search for it, then it will be returned ", function() {
            const pull_request = {
                id: 75
            };
            PullRequestCollectionService.all_pull_requests = [pull_request];

            const result = PullRequestCollectionService.search(75);

            expect(result).toBe(pull_request);
        });

        it("Given a pull request that was not stored in all the pull requests, when I search for it, then undefined will be returned", function() {
            const pull_request = {
                id: 98
            };
            PullRequestCollectionService.all_pull_requests = [pull_request];

            const result = PullRequestCollectionService.search(11);

            expect(result).toBeUndefined();
        });
    });
});

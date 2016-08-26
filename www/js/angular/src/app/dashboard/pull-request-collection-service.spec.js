describe('PullRequestCollectionService -', function() {
    var $q,
        PullRequestCollectionService,
        SharedPropertiesService,
        PullRequestCollectionRestService;

    beforeEach(function() {
        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(
            _$q_,
            _PullRequestCollectionRestService_,
            _PullRequestCollectionService_,
            _SharedPropertiesService_
        ) {
            $q                               = _$q_;
            PullRequestCollectionRestService = _PullRequestCollectionRestService_;
            PullRequestCollectionService     = _PullRequestCollectionService_;
            SharedPropertiesService          = _SharedPropertiesService_;
        });

        spyOn(PullRequestCollectionRestService, "getAllPullRequests");
        spyOn(SharedPropertiesService, "getRepositoryId");

        installPromiseMatchers();
    });

    describe('loadPullRequests()', function() {
        it("When I load the pull requests, then the REST service will be called and the returned pull requests will be stored by reverse order of creation date", function() {
            var pull_requests = [
                { id: 1 },
                { id: 2 }
            ];

            PullRequestCollectionRestService.getAllPullRequests.and.callFake(function(repository_id, callback) {
                callback(pull_requests);
                return $q.when(pull_requests);
            });
            SharedPropertiesService.getRepositoryId.and.returnValue(1);

            var promise = PullRequestCollectionService.loadPullRequests();

            var reversed_pull_requests = [
                { id: 2 },
                { id: 1 }
            ];

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toEqual(reversed_pull_requests);
        });

        it("Given that pull requests had already been loaded once, when I load the pull requests again, then the REST service will be called and only when all pull requests are retrieved, the pull requests will be stored", function() {
            PullRequestCollectionService.pull_requests_fully_loaded = true;

            var pull_requests = [
                { id: 9 },
                { id: 68 }
            ];

            PullRequestCollectionRestService.getAllPullRequests.and.callFake(function(repository_id, callback) {
                callback(pull_requests);
                return $q.when(pull_requests);
            });
            SharedPropertiesService.getRepositoryId.and.returnValue(1);

            var promise = PullRequestCollectionService.loadPullRequests();

            var reversed_pull_requests = [
                { id: 68 },
                { id: 9 }
            ];

            expect(promise).toBeResolved();
            expect(PullRequestCollectionService.all_pull_requests).toEqual(reversed_pull_requests);
        });
    });
});

describe("PullRequestCollectionRestService -", function() {
    var $q,
        $httpBackend,
        PullRequestCollectionRestService,
        ErrorModalService;

    beforeEach(function() {
        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(
            _$httpBackend_,
            _$q_,
            _ErrorModalService_,
            _PullRequestCollectionRestService_
        ) {
            $httpBackend                     = _$httpBackend_;
            $q                               = _$q_;
            ErrorModalService                = _ErrorModalService_;
            PullRequestCollectionRestService = _PullRequestCollectionRestService_;
        });

        spyOn(ErrorModalService, "showError");

        installPromiseMatchers();
    });

    afterEach(function() {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getPullRequests()", function() {
        it("Given a git repository id, a limit and offset, when I get the pull requests for the repository, then a GET request will be sent to Tuleap and an object containing the total number of pull requests and a collection of pull requests will be returned", function() {
            var total_pull_requests = 2;
            var headers = {
                'X-Pagination-Size': total_pull_requests
            };

            var pull_requests = [
                {
                    id         : 31,
                    title      : 'pharyngoplegic',
                    user_id    : 121,
                    branch_src : 'feature-adicity',
                    branch_dest: 'master',
                    repository : {
                        id: 4
                    },
                    repository_dest: {
                        id: 4
                    },
                    status       : 'abandon',
                    creation_date: '2016-01-06T19:21:13+00:00'
                }, {
                    id         : 8,
                    title      : 'asp',
                    user_id    : 120,
                    branch_src : 'feature-marblehead',
                    branch_dest: 'master',
                    repository : {
                        id: 16
                    },
                    repository_dest: {
                        id: 29
                    },
                    status       : 'review',
                    creation_date: '2010-08-17T11:02:09+00:00'
                }
            ];

            var repository_id = 26;

            $httpBackend.expectGET('/api/v1/git/' + repository_id + '/pull_requests?limit=50&offset=0')
            .respond(angular.toJson({
                collection: pull_requests
            }), headers);

            var promise = PullRequestCollectionRestService.getPullRequests(repository_id, 50, 0);
            $httpBackend.flush();

            expect(promise).toBeResolvedWith({
                results: pull_requests,
                total  : total_pull_requests
            });
        });

        it("when the server responds with an error, then the error modal will be shown", function() {
            var repository_id = 29;

            $httpBackend.expectGET('/api/v1/git/' + repository_id + '/pull_requests?limit=50&offset=0')
            .respond(403, 'Forbidden');

            var promise = PullRequestCollectionRestService.getPullRequests(repository_id, 50, 0);
            $httpBackend.flush();

            expect(ErrorModalService.showError).toHaveBeenCalledWith(jasmine.objectContaining({
                status    : 403,
                statusText: ''
            }));
            expect(promise).toBeRejected();
        });
    });

    describe("getAllPullRequests() -", function() {
        it("Given a git repository id and a progress callback, given a pagination limit of 2 and given there were 4 linked pull requests, when I get the pull requests for the repository, then two requests will be sent to Tuleap , for each resolved request the progress callback will be called with the results and a promise will be resolved with a single array containing all results", function() {
            var first_pull_requests = [
                {
                    id: 43
                }, {
                    id: 35
                }
            ];

            var second_pull_requests = [
                {
                    id: 18
                }, {
                    id: 31
                }
            ];
            spyOn(PullRequestCollectionRestService, "getPullRequests").and.callFake(function(repository_id, limit, offset) {
                if (offset === 0) {
                    return $q.when({
                        results: first_pull_requests,
                        total  : 4
                    });
                } else if (offset === 2) {
                    return $q.when({
                        results: second_pull_requests,
                        total  : 4
                    });
                }
            });

            PullRequestCollectionRestService.pull_requests_pagination.limit = 2;
            var repository_id     = 46;
            var progress_callback = jasmine.createSpy("progress_callback");
            var promise           = PullRequestCollectionRestService.getAllPullRequests(repository_id, progress_callback);

            var all_artifacts = first_pull_requests.concat(second_pull_requests);

            expect(promise).toBeResolvedWith(all_artifacts);
            expect(progress_callback).toHaveBeenCalledWith(first_pull_requests);
            expect(progress_callback).toHaveBeenCalledWith(second_pull_requests);
            expect(PullRequestCollectionRestService.getPullRequests).toHaveBeenCalledWith(repository_id, 2, 0);
            expect(PullRequestCollectionRestService.getPullRequests).toHaveBeenCalledWith(repository_id, 2, 2);
            expect(PullRequestCollectionRestService.getPullRequests.calls.count()).toBe(2);
        });
    });
});

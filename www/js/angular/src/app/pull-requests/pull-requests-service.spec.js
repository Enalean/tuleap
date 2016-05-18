describe('PullRequestsService', function() {
    var $httpBackend,
        PullRequestsService,
        SharedPropertiesService,
        lodash;

    beforeEach(function() {
        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(_$httpBackend_,
                        _PullRequestsService_,
                        _SharedPropertiesService_,
                        _lodash_
        ) {
            $httpBackend = _$httpBackend_;
            lodash = _lodash_;
            PullRequestsService = _PullRequestsService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });
    });

    describe('#getPullRequests', function() {
        var backendData;
        var repoId = '1', limit = 50, offset = 0;

        beforeEach(function() {
            backendData = {
                collection: [{
                    id         : 1,
                    title      : 'Asking a PR',
                    user_id    : 101,
                    branch_src : 'sample-pr',
                    branch_dest: 'master',
                    repository : {
                        id: 1
                    },
                    repository_dest: {
                        id: 1
                    },
                    status       : 'abandon',
                    creation_date: '1996-00-00T00:00:00+00:00'
                }, {
                    id         : 2,
                    title      : 'Asking another PR',
                    user_id    : 101,
                    branch_src : 'sample-pr',
                    branch_dest: 'master',
                    repository : {
                        id: 1
                    },
                    repository_dest: {
                        id: 2
                    },
                    status       : 'abandon',
                    creation_date: '2016-04-19T09:20:21+00:00'
                }]
            };
        });

        it('requests pull requests from the REST service', function() {
            var expectedUrl = '/api/v1/git/' + repoId + '/pull_requests?limit=' + limit + '&offset=' + offset;
            $httpBackend.expectGET(expectedUrl).respond([]);

            PullRequestsService.getPullRequests(repoId, limit, offset);

            $httpBackend.verifyNoOutstandingExpectation();
        });

        it('sets the list of pull requests in SharedPropertiesService by reverse order of creation date', function() {
            $httpBackend.whenGET().respond(backendData);

            PullRequestsService.getPullRequests(repoId, limit, offset);
            $httpBackend.flush();

            var processedPullRequests = SharedPropertiesService.getPullRequests();
            expect(lodash.map(processedPullRequests, 'id')).toEqual([2, 1]);
        });

        it('sets flags for each pull request', function() {
            $httpBackend.whenGET().respond(backendData);

            PullRequestsService.getPullRequests(repoId, limit, offset);
            $httpBackend.flush();

            var processedPullRequests = SharedPropertiesService.getPullRequests();
            expect(lodash.map(processedPullRequests, 'repository.isFork')).toEqual([true, false]);
            expect(lodash.map(processedPullRequests, 'repository.isCurrent')).toEqual([true, true]);
            expect(lodash.map(processedPullRequests, 'repository_dest.isCurrent')).toEqual([false, true]);
        });
    });
});

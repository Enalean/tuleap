describe('MainController', function() {
    var $q,
        $scope,
        $state,
        PullRequestsService,
        SharedPropertiesService,
        repoId,
        userId;

    beforeEach(function() {
        module('tuleap.pull-request');

        var $controller, $rootScope;

        // eslint-disable-next-line angular/di
        inject(function(_$controller_,
                        _$q_,
                        _$rootScope_,
                        _$state_,
                        _PullRequestsService_,
                        _SharedPropertiesService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope  = _$rootScope_;
            $state = _$state_;
            PullRequestsService = _PullRequestsService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        repoId = 1;
        userId = 101;

        $scope = $rootScope.$new();
        $controller('MainController', {
            $scope: $scope
        });
    });

    it('has an init method', function() {
        expect($scope.init).toBeTruthy();
    });

    describe('#init', function() {
        var expectedPullRequests, expectedPullRequest;

        beforeEach(function() {
            expectedPullRequests = [{
                id         : 1,
                title      : 'Asking a PR',
                user_id    : 101,
                branch_src : 'sample-pr',
                branch_dest: 'master',
                status     : 'abandon'
            }, {
                id         : 2,
                title      : 'Asking another PR',
                user_id    : 101,
                branch_src : 'sample-pr',
                branch_dest: 'master',
                status     : 'abandon'
            }];
            expectedPullRequest = expectedPullRequests[0];

            spyOn(PullRequestsService, 'getPullRequests').and.returnValue($q.when(expectedPullRequests));
        });

        it('sets some shared properties', function() {
            $scope.init(repoId, userId, 'fr');

            expect(SharedPropertiesService.getRepositoryId()).toEqual(repoId);
            expect(SharedPropertiesService.getUserId()).toEqual(userId);
        });

        it('loads shared pull requests data', function() {
            $scope.init(repoId, userId, 'fr');
            $scope.$apply();

            expect(PullRequestsService.getPullRequests).toHaveBeenCalledWith(repoId, jasmine.any(Number), jasmine.any(Number));
            expect(SharedPropertiesService.getPullRequest()).toEqual(expectedPullRequest);
        });

        it('resolves the ready promise', function() {
            var isReady = false;
            $scope.init(repoId, userId, 'fr');
            SharedPropertiesService.whenReady().then(function() {
                isReady = true;
            });
            $scope.$apply();

            expect(isReady).toBe(true);
        });

        describe('looks at the current state', function() {
            beforeEach(function() {
                spyOn($state, 'go');
            });

            it('and redirects to overview if in a parent state', function() {
                $state.current.name = 'pull-requests';
                $scope.init(repoId, userId, 'fr');
                $scope.$apply();

                expect($state.go).toHaveBeenCalledWith('overview', { id: expectedPullRequest.id });
            });

            it('does not redirect if in a child state', function() {
                $state.current.name = 'files';
                $scope.init(repoId, userId, 'fr');
                $scope.$apply();

                expect($state.go).not.toHaveBeenCalled();
            });
        });
    });
});

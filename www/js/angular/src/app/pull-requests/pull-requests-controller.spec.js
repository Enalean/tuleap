describe('PullRequestsController', function() {
    var $q,
        $rootScope,
        $state,
        SharedPropertiesService,
        controller,
        expectedPullRequests,
        selectedPullRequest;

    beforeEach(function() {
        module('tuleap.pull-request');

        var $controller;

        // eslint-disable-next-line angular/di
        inject(function(_$controller_,
                        _$q_,
                        _$rootScope_,
                        _$state_,
                        _SharedPropertiesService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope  = _$rootScope_;
            $state = _$state_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

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
        selectedPullRequest = expectedPullRequests[0];
        SharedPropertiesService.setPullRequests(expectedPullRequests);
        SharedPropertiesService.setPullRequest(selectedPullRequest);
        SharedPropertiesService.setReadyPromise($q.when('ready'));

        controller = $controller('PullRequestsController');
    });

    describe('When app is ready', function() {
        it('sets the list of pull requests and the current one', function() {
            $rootScope.$apply();

            expect(controller.pull_requests).toEqual(expectedPullRequests);
            expect(controller.selected_pull_request).toEqual(selectedPullRequest);
        });
    });

    describe('#loadPullRequest', function() {
        it('sets the current pull request', function() {
            var targetPullRequest = expectedPullRequests[1];
            controller.loadPullRequest(targetPullRequest);

            expect(SharedPropertiesService.getPullRequest()).toEqual(targetPullRequest);
        });

        it('navigates to the overview of the given pull request', function() {
            var targetPullRequest = expectedPullRequests[1];
            spyOn($state, 'go');
            controller.loadPullRequest(targetPullRequest);

            expect($state.go).toHaveBeenCalledWith('overview', { id: targetPullRequest.id });
        });
    });
});

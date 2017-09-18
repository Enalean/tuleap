describe("PullRequestSummaryController -", function() {
    var $q,
        $rootScope,
        PullRequestSummaryController,
        UserRestService;

    beforeEach(function() {
        var $controller;

        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _UserRestService_
        ) {
            $controller     = _$controller_;
            $q              = _$q_;
            $rootScope      = _$rootScope_;
            UserRestService = _UserRestService_;
        });

        spyOn(UserRestService, "getUser").and.returnValue($q.when());

        PullRequestSummaryController = $controller('PullRequestSummaryController', {}, {
            pull_request: {
                user_id: 134
            }
        });
    });

    describe("init()", function() {
        it("when I create the controller, then it will fetch the pull request's author using the REST service", function() {
            var user_id = 112;
            var user    = {
                id          : 112,
                display_name: "Oliver Haglund"
            };

            UserRestService.getUser.and.returnValue($q.when(user));
            PullRequestSummaryController.pull_request = {
                user_id: user_id
            };

            PullRequestSummaryController.init();
            $rootScope.$apply();

            expect(UserRestService.getUser).toHaveBeenCalledWith(user_id);
            expect(PullRequestSummaryController.author).toBe(user);
        });
    });
});

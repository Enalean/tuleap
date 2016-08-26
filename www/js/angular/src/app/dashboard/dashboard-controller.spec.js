describe('DashboardController', function() {
    var $q,
        $rootScope,
        DashboardController,
        PullRequestCollectionService,
        TooltipService;

    beforeEach(function() {
        var $controller;

        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _PullRequestCollectionService_,
            _TooltipService_
        ) {
            $controller                  = _$controller_;
            $q                           = _$q_;
            $rootScope                   = _$rootScope_;
            PullRequestCollectionService = _PullRequestCollectionService_;
            TooltipService               = _TooltipService_;
        });

        spyOn(PullRequestCollectionService, "loadPullRequests").and.returnValue($q.when());

        DashboardController = $controller('DashboardController', {
            PullRequestCollectionService: PullRequestCollectionService
        });
    });

    describe("init()", function() {
        it("when the controller is created, then all the pull requests will be loaded and the loading flag will be set to false", function() {
            spyOn(TooltipService, "setupTooltips");
            PullRequestCollectionService.loadPullRequests.and.returnValue($q.when());

            DashboardController.init();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadPullRequests).toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });
    });
});

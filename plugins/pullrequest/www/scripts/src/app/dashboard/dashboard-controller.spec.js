import angular from "angular";
import tuleap_pullrequest from "tuleap-pullrequest-module";
import dashboard_controller from "./dashboard-controller.js";

import "angular-mocks";

describe("DashboardController", function() {
    var $q, $rootScope, DashboardController, PullRequestCollectionService, TooltipService;

    beforeEach(function() {
        var $controller;

        angular.mock.module(tuleap_pullrequest);

        angular.mock.inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _PullRequestCollectionService_,
            _TooltipService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            PullRequestCollectionService = _PullRequestCollectionService_;
            TooltipService = _TooltipService_;
        });

        spyOn(PullRequestCollectionService, "loadOpenPullRequests").and.returnValue($q.when());
        spyOn(PullRequestCollectionService, "loadClosedPullRequests");
        spyOn(PullRequestCollectionService, "loadAllPullRequests");
        spyOn(PullRequestCollectionService, "areAllPullRequestsFullyLoaded").and.returnValue(false);
        spyOn(PullRequestCollectionService, "areClosedPullRequestsFullyLoaded");
        spyOn(TooltipService, "setupTooltips");

        DashboardController = $controller(dashboard_controller, {
            PullRequestCollectionService: PullRequestCollectionService
        });
    });

    describe("init()", function() {
        it("When the controller is created, then the open pull requests will be loaded and the loading flag will be set to false", function() {
            PullRequestCollectionService.loadOpenPullRequests.and.returnValue($q.when());

            DashboardController.$onInit();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadOpenPullRequests).toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });

        it("Given that all the pull requests had already been loaded before, when the controller is created, then all the pull requests will be reloaded", function() {
            PullRequestCollectionService.loadOpenPullRequests.calls.reset();
            PullRequestCollectionService.areAllPullRequestsFullyLoaded.and.returnValue(true);
            PullRequestCollectionService.loadAllPullRequests.and.returnValue($q.when());

            DashboardController.$onInit();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadAllPullRequests).toHaveBeenCalled();
            expect(PullRequestCollectionService.loadOpenPullRequests).not.toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });
    });

    describe("loadClosedPullRequests()", function() {
        beforeEach(function() {
            $rootScope.$apply();
        });

        it("When I load the closed pull requests, then the collection service will load closed pull requests, the closed pull requests will be shown and the loading flag will be set to false", function() {
            PullRequestCollectionService.loadClosedPullRequests.and.returnValue($q.when());

            DashboardController.loadClosedPullRequests();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadClosedPullRequests).toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });

        it("Given that all the closed pull requests had already been loaded before, when I load closed pull requests again, then they will be shown and the REST route will not be called again", function() {
            PullRequestCollectionService.areClosedPullRequestsFullyLoaded.and.returnValue(true);

            DashboardController.loadClosedPullRequests();
            $rootScope.$apply();

            expect(DashboardController.areClosedPullRequestsHidden()).toBe(false);
            expect(PullRequestCollectionService.loadClosedPullRequests).not.toHaveBeenCalled();
        });
    });

    describe("hideClosedPullRequests()", function() {
        it("When I hide closed pull requests, then the hidden flag will be true", function() {
            DashboardController.hideClosedPullRequests();

            expect(DashboardController.areClosedPullRequestsHidden()).toBe(true);
        });
    });
});

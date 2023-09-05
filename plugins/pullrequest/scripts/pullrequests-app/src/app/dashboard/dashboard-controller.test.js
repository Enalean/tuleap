import angular from "angular";
import tuleap_pullrequest from "../app.js";
import dashboard_controller from "./dashboard-controller.js";

import "angular-mocks";

describe("DashboardController", function () {
    var $q, $rootScope, DashboardController, PullRequestCollectionService, TooltipService;

    beforeEach(function () {
        var $controller;

        angular.mock.module(tuleap_pullrequest);

        angular.mock.inject(
            function (
                _$controller_,
                _$q_,
                _$rootScope_,
                _PullRequestCollectionService_,
                _TooltipService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                PullRequestCollectionService = _PullRequestCollectionService_;
                TooltipService = _TooltipService_;
            },
        );

        jest.spyOn(PullRequestCollectionService, "loadOpenPullRequests").mockReturnValue($q.when());
        jest.spyOn(PullRequestCollectionService, "loadClosedPullRequests").mockImplementation(
            () => {},
        );
        jest.spyOn(PullRequestCollectionService, "loadAllPullRequests").mockImplementation(
            () => {},
        );
        jest.spyOn(PullRequestCollectionService, "areAllPullRequestsFullyLoaded").mockReturnValue(
            false,
        );
        jest.spyOn(
            PullRequestCollectionService,
            "areClosedPullRequestsFullyLoaded",
        ).mockImplementation(() => {});
        jest.spyOn(TooltipService, "setupTooltips").mockImplementation(() => {});

        DashboardController = $controller(dashboard_controller, {
            PullRequestCollectionService: PullRequestCollectionService,
            $element: angular.element(),
        });
    });

    describe("init()", function () {
        it("When the controller is created, then the open pull requests will be loaded and the loading flag will be set to false", function () {
            PullRequestCollectionService.loadOpenPullRequests.mockReturnValue($q.when());

            DashboardController.$onInit();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadOpenPullRequests).toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });

        it("Given that all the pull requests had already been loaded before, when the controller is created, then all the pull requests will be reloaded", function () {
            PullRequestCollectionService.areAllPullRequestsFullyLoaded.mockReturnValue(true);
            PullRequestCollectionService.loadAllPullRequests.mockReturnValue($q.when());

            DashboardController.$onInit();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadAllPullRequests).toHaveBeenCalled();
            expect(PullRequestCollectionService.loadOpenPullRequests).not.toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });
    });

    describe("loadClosedPullRequests()", function () {
        beforeEach(function () {
            $rootScope.$apply();
        });

        it("When I load the closed pull requests, then the collection service will load closed pull requests, the closed pull requests will be shown and the loading flag will be set to false", function () {
            PullRequestCollectionService.loadClosedPullRequests.mockReturnValue($q.when());

            DashboardController.loadClosedPullRequests();
            expect(DashboardController.loading_pull_requests).toBe(true);

            $rootScope.$apply();

            expect(PullRequestCollectionService.loadClosedPullRequests).toHaveBeenCalled();
            expect(DashboardController.loading_pull_requests).toBe(false);
            expect(TooltipService.setupTooltips).toHaveBeenCalled();
        });

        it("Given that all the closed pull requests had already been loaded before, when I load closed pull requests again, then they will be shown and the REST route will not be called again", function () {
            PullRequestCollectionService.areClosedPullRequestsFullyLoaded.mockReturnValue(true);

            DashboardController.loadClosedPullRequests();
            $rootScope.$apply();

            expect(DashboardController.areClosedPullRequestsHidden()).toBe(false);
            expect(PullRequestCollectionService.loadClosedPullRequests).not.toHaveBeenCalled();
        });
    });
});

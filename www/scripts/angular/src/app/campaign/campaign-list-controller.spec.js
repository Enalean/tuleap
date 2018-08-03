import { rewire$getCampaigns, restore as restoreRest } from "../api/rest-querier.js";
import { rewire$setError, restore as restoreFeedback } from "../feedback-state.js";
import { mockFetchError } from "tlp-mocks";

import campaign_module from "./campaign.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./campaign-list-controller.js";

describe("CampaignListController -", () => {
    const project_id = 68;
    const milestone = { id: 85 };

    let $q,
        $scope,
        $filter,
        CampaignListController,
        SharedPropertiesService,
        TlpModalService,
        getCampaigns,
        setError;

    beforeEach(() => {
        angular.mock.module(campaign_module);

        let $controller;

        angular.mock.inject(function(
            _$q_,
            $rootScope,
            _$controller_,
            _$filter_,
            _SharedPropertiesService_,
            _TlpModalService_
        ) {
            $q = _$q_;
            $scope = $rootScope.$new();
            $controller = _$controller_;
            $filter = _$filter_;
            SharedPropertiesService = _SharedPropertiesService_;
            TlpModalService = _TlpModalService_;
        });

        spyOn(SharedPropertiesService, "getProjectId").and.returnValue(project_id);
        getCampaigns = jasmine.createSpy("getCampaigns").and.returnValue($q.defer().promise);
        rewire$getCampaigns(getCampaigns);

        CampaignListController = $controller(BaseController, {
            $scope,
            TlpModalService,
            $filter,
            SharedPropertiesService,
            milestone
        });

        setError = jasmine.createSpy("setError");
        rewire$setError(setError);

        installPromiseMatchers();
    });

    afterEach(() => {
        restoreRest();
        restoreFeedback();
    });

    describe("init() -", () => {
        it("When the controller is instatiated, then all the open campaigns will be loaded, then all the closed campaigns will be loaded", () => {
            const open_campaigns = [{ id: 60, status: "open" }, { id: 80, status: "open" }];
            const open_campaigns_data = $q.when(open_campaigns);
            const closed_campaigns = [{ id: 13, status: "closed" }, { id: 36, status: "closed" }];
            const closed_campaigns_data = $q.when(closed_campaigns);

            getCampaigns.and.callFake(
                (project_id, milestone_id, status) =>
                    status === "open" ? open_campaigns_data : closed_campaigns_data
            );

            const promise = CampaignListController.$onInit();

            expect($scope.loading).toBe(true);
            expect(getCampaigns).toHaveBeenCalledWith(project_id, milestone.id, "open");
            expect(promise).toBeResolved();

            expect($scope.filtered_campaigns).toEqual(open_campaigns);
            expect($scope.has_open_campaigns).toBe(true);

            expect(getCampaigns).toHaveBeenCalledWith(project_id, milestone.id, "closed");
            expect($scope.campaigns).toEqual(open_campaigns.concat(closed_campaigns));
            expect($scope.has_closed_campaigns).toBe(true);
            expect($scope.campaigns_loaded).toBe(true);
            expect($scope.loading).toBe(false);
        });

        it("When there is an error in one request, then an error message will be shown", done => {
            const error_json = {
                error: { code: 403, message: "Forbidden: Access denied to campaign tracker" }
            };
            const error = {
                response: {
                    json() {
                        return $q.when(error_json);
                    }
                }
            };
            getCampaigns.and.returnValue($q.reject(error));

            const promise = CampaignListController.$onInit().then(() => {
                expect(setError).toHaveBeenCalled();
                expect($scope.loading).toBe(false);
                done();
            }, fail);

            expect(promise).toBeResolved();
        });
    });

    describe("shouldShowNoCampaigns()", () => {
        it("Given the campaigns were loaded and there were campaigns, then it will return false", () => {
            $scope.campaigns_loaded = true;
            $scope.campaigns = [{ id: 66 }, { id: 8 }];

            expect($scope.shouldShowNoCampaigns()).toBe(false);
        });

        it("Given there were no campaigns, then it will return true", () => {
            $scope.campaigns_loaded = true;
            $scope.campaigns = [];

            expect($scope.shouldShowNoCampaigns()).toBe(true);
        });

        it("Given the campaigns were not loaded, then it will return false", () => {
            $scope.campaigns_loaded = false;
            $scope.campaigns = [{ id: 66 }, { id: 8 }];

            expect($scope.shouldShowNoCampaigns()).toBe(false);
        });
    });

    describe("shouldShowNoOpenCampaigns()", () => {
        it("Given the closed campaigns were hidden and the campaigns loaded and there are closed campaigns but no open campaigns, then it will return true", () => {
            $scope.campaigns_loaded = true;
            $scope.closed_campaigns_hidden = true;
            $scope.has_closed_campaigns = true;
            $scope.has_open_campaigns = false;

            expect($scope.shouldShowNoOpenCampaigns()).toBe(true);
        });

        it("Given the closed campaigns were not hidden, then it will return false", () => {
            $scope.campaigns_loaded = true;
            $scope.closed_campaigns_hidden = false;
            $scope.has_closed_campaigns = true;
            $scope.has_open_campaigns = false;

            expect($scope.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given there are no closed campaigns, then it will return false", () => {
            $scope.campaigns_loaded = true;
            $scope.closed_campaigns_hidden = true;
            $scope.has_closed_campaigns = false;
            $scope.has_open_campaigns = false;

            expect($scope.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given there are open campaigns, then it will return false", () => {
            $scope.campaigns_loaded = true;
            $scope.closed_campaigns_hidden = true;
            $scope.has_closed_campaigns = true;
            $scope.has_open_campaigns = true;

            expect($scope.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given the campaigns were not loaded, then it will return false", () => {
            $scope.campaigns_loaded = false;
            $scope.closed_campaigns_hidden = true;
            $scope.has_closed_campaigns = true;
            $scope.has_open_campaigns = false;

            expect($scope.shouldShowNoOpenCampaigns()).toBe(false);
        });
    });

    describe("showClosedCampaigns()", () => {
        it("The filtered campaigns will be updated and the boolean flag set to false", () => {
            const campaigns = [{ id: 42 }, { id: 7 }];
            $scope.campaigns = campaigns;
            $scope.filtered_campaigns = [];

            $scope.showClosedCampaigns();

            expect($scope.filtered_campaigns).toEqual(campaigns);
            expect($scope.closed_campaigns_hidden).toBe(false);
        });
    });

    describe("hideClosedCampaigns()", () => {
        it("The filtered campaigns will be updated and the boolean flag set to true", () => {
            const open_campaigns = [{ id: 70, status: "open" }, { id: 10, status: "open" }];
            $scope.campaigns = open_campaigns.concat([{ id: 78, status: "closed" }]);

            $scope.hideClosedCampaigns();

            expect($scope.filtered_campaigns).toEqual(open_campaigns);
            expect($scope.closed_campaigns_hidden).toBe(true);
        });
    });
});

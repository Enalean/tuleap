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

    let $q, $rootScope, $ctrl, SharedPropertiesService, TlpModalService, getCampaigns, setError;

    beforeEach(() => {
        angular.mock.module(campaign_module);

        let $controller;

        angular.mock.inject(function(
            _$q_,
            _$rootScope_,
            _$controller_,
            _SharedPropertiesService_,
            _TlpModalService_
        ) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            $controller = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
            TlpModalService = _TlpModalService_;
        });

        spyOn(SharedPropertiesService, "getProjectId").and.returnValue(project_id);
        getCampaigns = jasmine.createSpy("getCampaigns").and.returnValue($q.defer().promise);
        rewire$getCampaigns(getCampaigns);

        $ctrl = $controller(BaseController, {
            TlpModalService,
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
        it("When the controller is instatiated, then all the open campaigns will be loaded", () => {
            const open_campaigns = [{ id: 60, status: "open" }, { id: 80, status: "open" }];
            getCampaigns.and.returnValue($q.when(open_campaigns));

            const promise = $ctrl.$onInit();

            expect($ctrl.loading).toBe(true);
            expect(getCampaigns).toHaveBeenCalledWith(project_id, milestone.id, "open");
            expect(promise).toBeResolved();

            expect($ctrl.open_campaigns).toEqual(open_campaigns);
            expect($ctrl.campaigns).toEqual(open_campaigns);
            expect($ctrl.filtered_campaigns).toEqual(open_campaigns);
            expect($ctrl.has_open_campaigns).toBe(true);
            expect($ctrl.loading).toBe(false);
            expect($ctrl.open_campaigns_loaded).toBe(true);
        });

        it("When there is an error, then the error message will be shown", done => {
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

            const promise = $ctrl.$onInit().then(() => {
                expect(setError).toHaveBeenCalled();
                expect($ctrl.loading).toBe(false);
                done();
            }, fail);

            expect(promise).toBeResolved();
        });
    });

    describe("loadClosedCampaigns()", () => {
        it("The closed campaigns will be loaded", () => {
            const closed_campaigns = [{ id: 13, status: "closed" }, { id: 36, status: "closed" }];
            getCampaigns.and.returnValue($q.when(closed_campaigns));

            const open_campaigns = [{ id: 42, status: "open" }, { id: 7, status: "open" }];
            $ctrl.open_campaigns = open_campaigns;
            $ctrl.campaigns = open_campaigns;
            $ctrl.filtered_campaigns = [];

            const promise = $ctrl.loadClosedCampaigns();

            expect(promise).toBeResolved();
            expect(getCampaigns).toHaveBeenCalledWith(project_id, milestone.id, "closed");
            expect($ctrl.campaigns).toEqual(open_campaigns.concat(closed_campaigns));
        });

        it("When there is an error, then the error message will be shown", done => {
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

            const promise = $ctrl.loadClosedCampaigns().then(() => {
                expect(setError).toHaveBeenCalled();
                expect($ctrl.loading).toBe(false);
                done();
            }, fail);

            expect(promise).toBeResolved();
        });
    });

    describe("shouldShowNoCampaigns()", () => {
        it("Given the campaigns were loaded and there were campaigns, then it will return false", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.campaigns = [{ id: 66 }, { id: 8 }];

            expect($ctrl.shouldShowNoCampaigns()).toBe(false);
        });

        it("Given there were no campaigns, then it will return true", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.campaigns = [];

            expect($ctrl.shouldShowNoCampaigns()).toBe(true);
        });

        it("Given the campaigns were not loaded, then it will return false", () => {
            $ctrl.open_campaigns_loaded = false;
            $ctrl.closed_campaigns_loaded = false;
            $ctrl.campaigns = [{ id: 66 }, { id: 8 }];

            expect($ctrl.shouldShowNoCampaigns()).toBe(false);
        });
    });

    describe("shouldShowNoOpenCampaigns()", () => {
        beforeEach(() => {
            spyOn($ctrl, "shouldShowNoCampaigns");
        });

        it("Given the open campaigns were loaded and there are no open campaigns and there are campaigns, then it will return true", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.has_open_campaigns = false;
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(true);
        });

        it("Given there are no campaigns at all, then it will return false", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.has_open_campaigns = false;
            $ctrl.shouldShowNoCampaigns.and.returnValue(true);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given there are open campaigns, then it will return false", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.has_open_campaigns = true;
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given the open campaigns were not loaded, then it will return false", () => {
            $ctrl.open_campaigns_loaded = false;
            $ctrl.has_open_campaigns = false;
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(false);
        });
    });

    describe("shouldShowLoadClosedButton()", () => {
        beforeEach(() => {
            spyOn($ctrl, "shouldShowNoCampaigns");
        });

        it("Given the closed campaigns were not loaded, then it will return true", () => {
            $ctrl.closed_campaigns_loaded = false;
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(true);
        });

        it("Given the closed campaigns were hidden and there are campaigns, then it will return true", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.closed_campaigns_hidden = true;
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(true);
        });

        it("Given the closed campaigns were hidden but there aren't any campaigns, then it will return false", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.closed_campaigns_hidden = true;
            $ctrl.shouldShowNoCampaigns.and.returnValue(true);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(false);
        });

        it("Given the closed campaigns were loaded and not hidden, then it will return false", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.closed_campaigns_hidden = false;
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(false);
        });
    });

    describe("shouldShowHideClosedButton()", () => {
        beforeEach(() => {
            spyOn($ctrl, "shouldShowNoCampaigns");
        });

        it("Given the closed campaigns were shown and there are campaigns, then it will return true", () => {
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);
            $ctrl.closed_campaigns_hidden = false;

            expect($ctrl.shouldShowHideClosedButton()).toBe(true);
        });

        it("Given there aren't any campaigns, then it will return false", () => {
            $ctrl.shouldShowNoCampaigns.and.returnValue(true);
            $ctrl.closed_campaigns_hidden = false;

            expect($ctrl.shouldShowHideClosedButton()).toBe(false);
        });

        it("Given the closed campaigns were hidden, then it will return false", () => {
            $ctrl.shouldShowNoCampaigns.and.returnValue(false);
            $ctrl.closed_campaigns_hidden = true;

            expect($ctrl.shouldShowHideClosedButton()).toBe(false);
        });
    });

    describe("showClosedCampaigns", () => {
        beforeEach(() => {
            spyOn($ctrl, "loadClosedCampaigns");
        });

        it("Given the closed campaigns have never been loaded, then they will be loaded, the filtered campaigns will be updated and the boolean flag set to false", () => {
            $ctrl.closed_campaigns_loaded = false;
            $ctrl.filtered_campaigns = [];
            const campaigns = [{ id: 42 }, { id: 26 }];
            $ctrl.campaigns = campaigns;

            $ctrl.showClosedCampaigns();

            $rootScope.$apply();
            expect($ctrl.loadClosedCampaigns).toHaveBeenCalled();
            expect($ctrl.closed_campaigns_hidden).toBe(false);
            expect($ctrl.filtered_campaigns).toEqual(campaigns);
        });

        it("Given the closed campaigns were already loaded, then they won't be queried twice", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.filtered_campaigns = [];
            const campaigns = [{ id: 42 }, { id: 26 }];
            $ctrl.campaigns = campaigns;

            $ctrl.showClosedCampaigns();

            $rootScope.$apply();
            expect($ctrl.loadClosedCampaigns).not.toHaveBeenCalled();
            expect($ctrl.closed_campaigns_hidden).toBe(false);
            expect($ctrl.filtered_campaigns).toEqual(campaigns);
        });
    });

    describe("hideClosedCampaigns()", () => {
        it("the filtered campaigns will be updated and the boolean flag set to true", () => {
            const open_campaigns = [{ id: 70, status: "open" }, { id: 10, status: "open" }];
            $ctrl.open_campaigns = open_campaigns;
            $ctrl.campaigns = open_campaigns.concat([{ id: 78, status: "closed" }]);

            $ctrl.hideClosedCampaigns();

            expect($ctrl.filtered_campaigns).toEqual(open_campaigns);
            expect($ctrl.closed_campaigns_hidden).toBe(true);
        });
    });
});

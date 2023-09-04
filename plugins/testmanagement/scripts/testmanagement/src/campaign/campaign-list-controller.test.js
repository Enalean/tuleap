/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import * as rest_querier from "../api/rest-querier.js";
import * as feedback_state from "../feedback-state.js";

import campaign_module from "./campaign.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./campaign-list-controller.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

jest.mock("../keyboard-navigation/setup-shortcuts");

describe("CampaignListController -", () => {
    const project_id = 68;
    const milestone = { id: 85 };

    let $q,
        $rootScope,
        $ctrl,
        wrapPromise,
        SharedPropertiesService,
        TlpModalService,
        getCampaigns,
        setError;

    beforeEach(() => {
        angular.mock.module(campaign_module);

        let $controller;

        angular.mock.inject(
            function (
                _$q_,
                _$rootScope_,
                _$controller_,
                _SharedPropertiesService_,
                _TlpModalService_,
            ) {
                $q = _$q_;
                $rootScope = _$rootScope_;
                $controller = _$controller_;
                SharedPropertiesService = _SharedPropertiesService_;
                TlpModalService = _TlpModalService_;
            },
        );

        wrapPromise = createAngularPromiseWrapper($rootScope);

        jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(project_id);
        getCampaigns = jest
            .spyOn(rest_querier, "getCampaigns")
            .mockImplementation(() => $q.defer().promise);

        $ctrl = $controller(BaseController, {
            TlpModalService,
            SharedPropertiesService,
            milestone,
        });

        setError = jest.spyOn(feedback_state, "setError");
    });

    describe("init()", () => {
        it("When the controller is instatiated, then all the open campaigns will be loaded", async () => {
            const open_campaigns = [
                { id: 60, status: "open" },
                { id: 80, status: "open" },
            ];
            getCampaigns.mockReturnValue($q.when(open_campaigns));

            const promise = $ctrl.$onInit();

            expect($ctrl.loading).toBe(true);
            expect(getCampaigns).toHaveBeenCalledWith(project_id, milestone.id, "open");
            await wrapPromise(promise);

            expect($ctrl.open_campaigns).toEqual(open_campaigns);
            expect($ctrl.campaigns).toEqual(open_campaigns);
            expect($ctrl.filtered_campaigns).toEqual(open_campaigns);
            expect($ctrl.has_open_campaigns).toBe(true);
            expect($ctrl.loading).toBe(false);
            expect($ctrl.open_campaigns_loaded).toBe(true);
        });

        it("When there is an error, then the error message will be shown", async () => {
            const error_json = {
                error: { code: 403, message: "Forbidden: Access denied to campaign tracker" },
            };
            const error = {
                response: {
                    json() {
                        return $q.when(error_json);
                    },
                },
            };
            getCampaigns.mockReturnValue($q.reject(error));

            const promise = $ctrl.$onInit();
            await wrapPromise(promise);

            expect(setError).toHaveBeenCalled();
            expect($ctrl.loading).toBe(false);
        });
    });

    describe("loadClosedCampaigns()", () => {
        it("The closed campaigns will be loaded", async () => {
            const closed_campaigns = [
                { id: 13, status: "closed" },
                { id: 36, status: "closed" },
            ];
            getCampaigns.mockReturnValue($q.when(closed_campaigns));

            const open_campaigns = [
                { id: 42, status: "open" },
                { id: 7, status: "open" },
            ];
            $ctrl.open_campaigns = open_campaigns;
            $ctrl.campaigns = open_campaigns;
            $ctrl.filtered_campaigns = [];

            const promise = $ctrl.loadClosedCampaigns();

            await wrapPromise(promise);
            expect(getCampaigns).toHaveBeenCalledWith(project_id, milestone.id, "closed");
            expect($ctrl.campaigns).toEqual(open_campaigns.concat(closed_campaigns));
        });

        it("When there is an error, then the error message will be shown", async () => {
            const error_json = {
                error: { code: 403, message: "Forbidden: Access denied to campaign tracker" },
            };
            const error = {
                response: {
                    json() {
                        return $q.when(error_json);
                    },
                },
            };
            getCampaigns.mockReturnValue($q.reject(error));

            const promise = $ctrl.loadClosedCampaigns();
            await wrapPromise(promise);
            expect(setError).toHaveBeenCalled();
            expect($ctrl.loading).toBe(false);
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
            jest.spyOn($ctrl, "shouldShowNoCampaigns").mockImplementation(() => {});
        });

        it("Given the open campaigns were loaded and there are no open campaigns and there are campaigns, then it will return true", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.has_open_campaigns = false;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(true);
        });

        it("Given there are no campaigns at all, then it will return false", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.has_open_campaigns = false;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(true);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given there are open campaigns, then it will return false", () => {
            $ctrl.open_campaigns_loaded = true;
            $ctrl.has_open_campaigns = true;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(false);
        });

        it("Given the open campaigns were not loaded, then it will return false", () => {
            $ctrl.open_campaigns_loaded = false;
            $ctrl.has_open_campaigns = false;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);

            expect($ctrl.shouldShowNoOpenCampaigns()).toBe(false);
        });
    });

    describe("shouldShowLoadClosedButton()", () => {
        beforeEach(() => {
            jest.spyOn($ctrl, "shouldShowNoCampaigns").mockImplementation(() => {});
        });

        it("Given the closed campaigns were not loaded, then it will return true", () => {
            $ctrl.closed_campaigns_loaded = false;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(true);
        });

        it("Given the closed campaigns were hidden and there are campaigns, then it will return true", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.closed_campaigns_hidden = true;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(true);
        });

        it("Given the closed campaigns were hidden but there aren't any campaigns, then it will return false", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.closed_campaigns_hidden = true;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(true);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(false);
        });

        it("Given the closed campaigns were loaded and not hidden, then it will return false", () => {
            $ctrl.closed_campaigns_loaded = true;
            $ctrl.closed_campaigns_hidden = false;
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);

            expect($ctrl.shouldShowLoadClosedButton()).toBe(false);
        });
    });

    describe("shouldShowHideClosedButton()", () => {
        beforeEach(() => {
            jest.spyOn($ctrl, "shouldShowNoCampaigns").mockImplementation(() => {});
        });

        it("Given the closed campaigns were shown and there are campaigns, then it will return true", () => {
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);
            $ctrl.closed_campaigns_hidden = false;

            expect($ctrl.shouldShowHideClosedButton()).toBe(true);
        });

        it("Given there aren't any campaigns, then it will return false", () => {
            $ctrl.shouldShowNoCampaigns.mockReturnValue(true);
            $ctrl.closed_campaigns_hidden = false;

            expect($ctrl.shouldShowHideClosedButton()).toBe(false);
        });

        it("Given the closed campaigns were hidden, then it will return false", () => {
            $ctrl.shouldShowNoCampaigns.mockReturnValue(false);
            $ctrl.closed_campaigns_hidden = true;

            expect($ctrl.shouldShowHideClosedButton()).toBe(false);
        });
    });

    describe("showClosedCampaigns", () => {
        beforeEach(() => {
            jest.spyOn($ctrl, "loadClosedCampaigns").mockImplementation(() => {});
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
            const open_campaigns = [
                { id: 70, status: "open" },
                { id: 10, status: "open" },
            ];
            $ctrl.open_campaigns = open_campaigns;
            $ctrl.campaigns = open_campaigns.concat([{ id: 78, status: "closed" }]);

            $ctrl.hideClosedCampaigns();

            expect($ctrl.filtered_campaigns).toEqual(open_campaigns);
            expect($ctrl.closed_campaigns_hidden).toBe(true);
        });
    });
});

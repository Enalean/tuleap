/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import testmanagement_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import * as tlp_fetch from "@tuleap/tlp-fetch";

describe("CampaignService", () => {
    let $q, wrapPromise, CampaignService;

    beforeEach(() => {
        angular.mock.module(testmanagement_module);

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _$q_, _CampaignService_) {
            $rootScope = _$rootScope_;
            $q = _$q_;
            CampaignService = _CampaignService_;
        });
        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    function mockFetchError(spy_function, { status, statusText, error_json } = {}) {
        spy_function.mockReturnValue(
            $q.reject({
                response: {
                    status,
                    statusText,
                    json: () => $q.when(error_json),
                },
            }),
        );
    }

    const expected_headers = { "content-type": "application/json" };

    describe(`getCampaign()`, () => {
        it(`will call GET on the testmanagement_campaigns and return the test campaign`, async () => {
            const campaign = { id: 98 };
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, { return_json: campaign });

            const promise = CampaignService.getCampaign(98);
            const result = await wrapPromise(promise);

            expect(result).toEqual(campaign);
        });
    });

    describe(`createCampaign()`, () => {
        let campaign_to_create, tlpPost;
        beforeEach(() => {
            campaign_to_create = { label: "Test Campaign", project_id: 101 };
            tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost);
        });

        it(`given a milestone and a report, it will call POST on the testmanagement_campaigns
            and create a campaign`, async () => {
            const milestone_id = "133";
            const report_id = "24";
            const promise = CampaignService.createCampaign(
                campaign_to_create,
                "report",
                milestone_id,
                report_id,
            );
            await wrapPromise(promise);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/v1/testmanagement_campaigns?test_selector=report&milestone_id=133&report_id=24`,
                { headers: expected_headers, body: JSON.stringify(campaign_to_create) },
            );
        });

        it(`given no milestone, it will create a campaign not linked to a milestone`, async () => {
            const report_id = "24";
            const promise = CampaignService.createCampaign(
                campaign_to_create,
                "report",
                undefined,
                report_id,
            );
            await wrapPromise(promise);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/v1/testmanagement_campaigns?test_selector=report&report_id=24`,
                { headers: expected_headers, body: JSON.stringify(campaign_to_create) },
            );
        });

        it(`given no report, it will create a campaign with all tests`, async () => {
            const milestone_id = "133";
            const promise = CampaignService.createCampaign(
                campaign_to_create,
                "all",
                milestone_id,
                null,
            );
            await wrapPromise(promise);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/v1/testmanagement_campaigns?test_selector=all&milestone_id=133`,
                { headers: expected_headers, body: JSON.stringify(campaign_to_create) },
            );
        });

        it(`given neither milestone nor report, it will create a campaign`, async () => {
            const promise = CampaignService.createCampaign(
                campaign_to_create,
                "all",
                undefined,
                null,
            );
            await wrapPromise(promise);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/v1/testmanagement_campaigns?test_selector=all`,
                {
                    headers: expected_headers,
                    body: JSON.stringify(campaign_to_create),
                },
            );
        });
    });

    it("patchCampaign()", async () => {
        const label = "cloiochoanitic";
        const job_configuration = {
            url: "https://example.com/badan/",
            token: "phrenicopericardiac",
        };
        const patched_campaign = { id: 17, label, job_configuration };
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch, { return_json: patched_campaign });

        const promise = CampaignService.patchCampaign(17, label, job_configuration);
        const response = await wrapPromise(promise);

        expect(tlpPatch).toHaveBeenCalledWith("/api/v1/testmanagement_campaigns/17", {
            headers: expected_headers,
            body: JSON.stringify({ label, job_configuration }),
        });
        expect(response).toEqual(patched_campaign);
    });

    it("patchExecutions()", async () => {
        const definition_ids = [1, 2];
        const execution_ids = [4];
        const executions = [
            {
                id: 1,
                previous_result: {
                    status: "notrun",
                },
            },
            {
                id: 2,
                previous_result: {
                    status: "notrun",
                },
            },
        ];

        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch, { headers: { get: () => "2" }, return_json: executions });

        const promise = CampaignService.patchExecutions(17, definition_ids, execution_ids);
        const response = await wrapPromise(promise);

        expect(response.total).toBe("2");
        expect(response.results).toEqual(executions);
        expect(tlpPatch).toHaveBeenCalledWith(
            `/api/v1/testmanagement_campaigns/17/testmanagement_executions`,
            {
                headers: expected_headers,
                body: JSON.stringify({
                    definition_ids_to_add: definition_ids,
                    execution_ids_to_remove: execution_ids,
                }),
            },
        );
    });

    describe("triggerAutomatedTests()", () => {
        it(`When the server responds with code 200, then a promise will be resolved`, async () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(tlpPost);

            const promise = CampaignService.triggerAutomatedTests(53);
            await wrapPromise(promise);

            expect(tlpPost).toHaveBeenCalledWith(
                `/api/v1/testmanagement_campaigns/53/automated_tests`,
            );
        });

        it(`When the server responds with code 500, then a promise will be rejected`, () => {
            const tlpPost = jest.spyOn(tlp_fetch, "post");
            mockFetchError(tlpPost, {
                error_json: {
                    error: { message: "Message: The requested URL returned error: 403 Forbidden" },
                },
            });

            expect.assertions(1);
            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = CampaignService.triggerAutomatedTests(31).catch((error) => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(error.message).toBe(
                    "Message: The requested URL returned error: 403 Forbidden",
                );
            });
            return wrapPromise(promise);
        });
    });
});

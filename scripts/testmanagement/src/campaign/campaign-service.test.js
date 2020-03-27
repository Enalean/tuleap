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
import { createAngularPromiseWrapper } from "../../../../../../tests/jest/angular-promise-wrapper.js";

describe("CampaignService", () => {
    let mockBackend, wrapPromise, CampaignService, SharedPropertiesService;
    const userUUID = "123";

    beforeEach(() => {
        angular.mock.module(testmanagement_module);

        let $rootScope;
        angular.mock.inject(function (
            _$rootScope_,
            _CampaignService_,
            $httpBackend,
            _SharedPropertiesService_
        ) {
            $rootScope = _$rootScope_;
            CampaignService = _CampaignService_;
            mockBackend = $httpBackend;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        jest.spyOn(SharedPropertiesService, "getUUID").mockReturnValue(userUUID);
        mockBackend.when("GET", "campaign-list.tpl.html").respond(200);

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("createCampaign()", async () => {
        var campaign_to_create = {
            label: "Release",
            project_id: 101,
        };
        var milestone_id = "133";
        var report_id = "24";
        var test_selector = "report";
        var campaign_created = {
            id: 17,
            tracker: {
                id: 11,
                uri: "trackers/11",
                label: "Validation Campaign",
            },
            uri: "artifacts/17",
        };
        var expected_request =
            "/api/v1/testmanagement_campaigns" +
            "?milestone_id=" +
            milestone_id +
            "&report_id=" +
            report_id +
            "&test_selector=" +
            test_selector;

        mockBackend
            .expectPOST(expected_request, campaign_to_create)
            .respond(JSON.stringify(campaign_created));

        var promise = CampaignService.createCampaign(
            campaign_to_create,
            test_selector,
            milestone_id,
            report_id
        );

        mockBackend.flush();

        const response = await wrapPromise(promise);
        expect(response.data.id).toEqual(17);
    });

    it("patchCampaign()", async () => {
        const label = "cloiochoanitic";
        const job_configuration = {
            url: "https://example.com/badan/",
            token: "phrenicopericardiac",
        };
        const executions = [
            {
                id: 4,
                previous_result: {
                    status: "notrun",
                },
            },
        ];

        mockBackend
            .expectPATCH("/api/v1/testmanagement_campaigns/17", {
                label,
                job_configuration,
            })
            .respond(executions);

        const promise = CampaignService.patchCampaign(17, label, job_configuration);

        mockBackend.flush();

        const response = await wrapPromise(promise);
        expect(response.length).toEqual(1);
    });

    it("patchExecutions()", async () => {
        var definition_ids = [1, 2],
            execution_ids = [4],
            executions = [
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

        mockBackend
            .expectPATCH("/api/v1/testmanagement_campaigns/17/testmanagement_executions", {
                uuid: userUUID,
                definition_ids_to_add: definition_ids,
                execution_ids_to_remove: execution_ids,
            })
            .respond(executions);

        var promise = CampaignService.patchExecutions(17, definition_ids, execution_ids);

        mockBackend.flush();

        const response = await wrapPromise(promise);
        expect(response.results.length).toEqual(2);
    });

    describe("triggerAutomatedTests() -", () => {
        it("When the server responds with code 200, then a promise will be resolved", () => {
            mockBackend
                .expectPOST("/api/v1/testmanagement_campaigns/53/automated_tests")
                .respond(200);

            const promise = CampaignService.triggerAutomatedTests(53);
            mockBackend.flush();

            return wrapPromise(promise);
        });

        it("When the server responds with code 500, then a promise will be rejected", () => {
            mockBackend
                .expectPOST("/api/v1/testmanagement_campaigns/31/automated_tests")
                .respond(500, {
                    error: { message: "Message: The requested URL returned error: 403 Forbidden" },
                });

            expect.assertions(1);
            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = CampaignService.triggerAutomatedTests(31).catch((error) => {
                expect(error.message).toEqual(
                    "Message: The requested URL returned error: 403 Forbidden"
                );
            });
            mockBackend.flush();
            return wrapPromise(promise);
        });
    });
});

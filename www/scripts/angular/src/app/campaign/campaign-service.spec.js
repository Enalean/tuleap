import testmanagement_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("CampaignService - ", () => {
    let mockBackend, CampaignService, SharedPropertiesService;
    const userUUID = "123";

    beforeEach(() => {
        angular.mock.module(testmanagement_module);

        angular.mock.inject(function(_CampaignService_, $httpBackend, _SharedPropertiesService_) {
            CampaignService = _CampaignService_;
            mockBackend = $httpBackend;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getUUID").and.returnValue(userUUID);

        installPromiseMatchers();
    });

    afterEach(() => {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("createCampaign() - ", function() {
        var campaign_to_create = {
            label: "Release",
            project_id: 101
        };
        var milestone_id = "133";
        var report_id = "24";
        var test_selector = "report";
        var campaign_created = {
            id: 17,
            tracker: {
                id: 11,
                uri: "trackers/11",
                label: "Validation Campaign"
            },
            uri: "artifacts/17"
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

        promise.then(function(response) {
            expect(response.data.id).toEqual(17);
        });
    });

    it("patchCampaign() - ", () => {
        const label = "cloiochoanitic";
        const job_configuration = {
            url: "https://example.com/badan/",
            token: "phrenicopericardiac"
        };
        const executions = [
            {
                id: 4,
                previous_result: {
                    status: "notrun"
                }
            }
        ];

        mockBackend
            .expectPATCH("/api/v1/testmanagement_campaigns/17", {
                label,
                job_configuration
            })
            .respond(executions);

        const promise = CampaignService.patchCampaign(17, label, job_configuration);

        mockBackend.flush();

        promise.then(executions => {
            expect(executions.length).toEqual(1);
        });
    });

    it("patchExecutions() - ", function() {
        var definition_ids = [1, 2],
            execution_ids = [4],
            executions = [
                {
                    id: 1,
                    previous_result: {
                        status: "notrun"
                    }
                },
                {
                    id: 2,
                    previous_result: {
                        status: "notrun"
                    }
                }
            ];

        mockBackend
            .expectPATCH("/api/v1/testmanagement_campaigns/17/testmanagement_executions", {
                uuid: userUUID,
                definition_ids_to_add: definition_ids,
                execution_ids_to_remove: execution_ids
            })
            .respond(executions);

        var promise = CampaignService.patchExecutions(17, definition_ids, execution_ids);

        mockBackend.flush();

        promise.then(function(response) {
            expect(response.results.length).toEqual(2);
        });
    });

    describe("triggerAutomatedTests() -", () => {
        it("When the server responds with code 200, then a promise will be resolved", () => {
            mockBackend
                .expectPOST("/api/v1/testmanagement_campaigns/53/automated_tests")
                .respond(200);

            const promise = CampaignService.triggerAutomatedTests(53);

            expect(promise).toBeResolved();
        });

        it("When the server responds with code 500, then a promise will be rejected ", () => {
            mockBackend
                .expectPOST("/api/v1/testmanagement_campaigns/31/automated_tests")
                .respond(500, {
                    error: { message: "Message: The requested URL returned error: 403 Forbidden" }
                });

            const promise = CampaignService.triggerAutomatedTests(31);

            expect(promise).toBeRejectedWith({
                message: "Message: The requested URL returned error: 403 Forbidden"
            });
        });
    });
});

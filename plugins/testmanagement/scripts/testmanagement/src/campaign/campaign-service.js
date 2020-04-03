export default CampaignService;

CampaignService.$inject = ["$http", "$q", "Restangular", "SharedPropertiesService"];

function CampaignService($http, $q, Restangular, SharedPropertiesService) {
    var rest = Restangular.withConfig(function (RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    return {
        getCampaign,
        createCampaign,
        patchCampaign,
        patchExecutions,
        triggerAutomatedTests,
    };

    function getCampaign(campaign_id) {
        return rest
            .one("testmanagement_campaigns", campaign_id)
            .get()
            .then((response) => {
                return response.data;
            });
    }

    function createCampaign(campaign, test_selector, milestone_id, report_id) {
        var queryParams = {
            test_selector: test_selector,
            milestone_id: milestone_id,
            report_id: report_id,
        };
        return rest.all("testmanagement_campaigns").post(campaign, queryParams);
    }

    function patchCampaign(campaign_id, label, job_configuration) {
        return rest
            .one("testmanagement_campaigns", campaign_id)
            .patch({
                label,
                job_configuration,
            })
            .then((response) => response.data);
    }

    function patchExecutions(campaign_id, definition_ids, execution_ids) {
        return rest
            .one("testmanagement_campaigns", campaign_id)
            .one("testmanagement_executions")
            .patch({
                uuid: SharedPropertiesService.getUUID(),
                definition_ids_to_add: definition_ids,
                execution_ids_to_remove: execution_ids,
            })
            .then(function (response) {
                var result = {
                    results: response.data,
                    total: response.headers("X-PAGINATION-SIZE"),
                };

                return result;
            });
    }

    function triggerAutomatedTests(campaign_id) {
        return $http
            .post(`/api/v1/testmanagement_campaigns/${campaign_id}/automated_tests`)
            .catch((response) => {
                return $q.reject(response.data.error);
            });
    }
}

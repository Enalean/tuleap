angular
    .module('campaign')
    .service('CampaignService', CampaignService);

CampaignService.$inject = [
    'Restangular'
];

function CampaignService(
    Restangular
) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getCampaign    : getCampaign,
        getCampaigns   : getCampaigns,
        createCampaign : createCampaign,
        patchCampaign  : patchCampaign
    };

    function getCampaign(campaign_id) {
        return rest.one('trafficlights_campaigns', campaign_id).get().$object;
    }

    function getCampaigns(project_id, campaign_status, limit, offset) {
        return rest.one('projects', project_id)
            .all('trafficlights_campaigns')
            .getList({
                limit: limit,
                offset: offset,
                query : {
                    status: campaign_status
                }
            })
            .then(function(response) {
                result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function createCampaign(campaign) {
        return rest.all('trafficlights_campaigns')
            .post(campaign);
    }

    function patchCampaign(campaign_id, execution_ids) {
        return rest.one('trafficlights_campaigns', campaign_id)
            .patch({
                execution_ids: execution_ids
            })
            .then(function(response) {
                return response.data;
            });
    }
}

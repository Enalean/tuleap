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
        getEnvironments: getEnvironments,
        createCampaign : createCampaign
    };

    function getCampaign(campaign_id) {
        return rest.one('trafficlights_campaigns', campaign_id).get().$object;
    }

    function getCampaigns(project_id, limit, offset) {
        return rest.one('projects', project_id)
            .all('trafficlights_campaigns')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function getEnvironments(campaign_id, limit, offset) {
        return rest.one('trafficlights_campaigns', campaign_id)
            .all('trafficlights_environments')
            .getList({
                limit: limit,
                offset: offset
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
}
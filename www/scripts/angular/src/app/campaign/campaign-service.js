angular
    .module('campaign')
    .service('CampaignService', CampaignService);

CampaignService.$inject = ['Restangular', '$q'];

function CampaignService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getCampaign    : getCampaign,
        getCampaigns   : getCampaigns,
        getAssignees   : getAssignees,
        getEnvironments: getEnvironments
    };

    function getCampaign(campaign_id) {
        return rest.one('testing_campaigns', campaign_id).get().$object;
    }

    function getCampaigns(project_id, limit, offset) {
        var data = $q.defer();

        rest.one('projects', project_id)
            .all('testing_campaigns')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getAssignees(campaign_id, limit, offset) {
        var data = $q.defer();

        rest.one('testing_campaigns', campaign_id)
            .all('testing_assignees')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getEnvironments(campaign_id, limit, offset) {
        var data = $q.defer();

        rest.one('testing_campaigns', campaign_id)
            .all('testing_environments')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }
}
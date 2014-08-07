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
        getCampaigns: getCampaigns
    };

    function getCampaigns(project_id, limit, offset) {
        var data = $q.defer();

        rest.one('projects', project_id)
            .all('campaigns')
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
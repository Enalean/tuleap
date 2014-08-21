angular
    .module('campaign')
    .service('ExecutionService', ExecutionService);

ExecutionService.$inject = ['Restangular', '$q'];

function ExecutionService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getExecutions: getExecutions
    };

    function getExecutions(campaign_id, limit, offset) {
        var data = $q.defer();

        rest.one('testing_campaigns', campaign_id)
            .all('testing_executions')
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
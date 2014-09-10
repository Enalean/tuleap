angular
    .module('campaign')
    .service('ExecutionService', ExecutionService);

ExecutionService.$inject = ['Restangular', '$q'];

function ExecutionService(Restangular, $q) {
    var baseurl = '/api/v1',
        rest = Restangular.withConfig(setRestangularConfig);

    return {
        getExecutions: getExecutions,
        putExecution: putExecution
    };

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

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

    function putExecution(execution) {
        // Unfortunately, we cannot use execution.save() or execution.put() since
        // the nested resources in restangular use nested uri. This means that
        // execution.put() will call /campaigns/:id/executions/:id instead of
        // /executions/:id
        return rest
            .restangularizeElement(null, execution, 'testing_executions')
            .put();
    }
}
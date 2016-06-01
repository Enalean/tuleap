angular
    .module('environment')
    .service('EnvironmentService', EnvironmentService);

EnvironmentService.$inject = ['Restangular', '$q'];

function EnvironmentService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getEnvironments: getEnvironments
    };

    function getEnvironments(project_id, limit, offset) {
        var data = $q.defer();

        rest.one('projects', project_id)
            .all('trafficlights_environments')
            .getList({
                limit     : limit,
                offset    : offset
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

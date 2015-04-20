angular
    .module('definition')
    .service('DefinitionService', DefinitionService);

DefinitionService.$inject = ['Restangular', '$q'];

function DefinitionService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getDefinitions: getDefinitions
    };

    function getDefinitions(project_id, limit, offset) {
        var data = $q.defer();

        rest.one('projects', project_id)
            .all('testing_definitions')
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

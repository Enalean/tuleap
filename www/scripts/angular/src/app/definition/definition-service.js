(function () {
    angular
        .module('definition')
        .service('DefinitionService', DefinitionService);

    DefinitionService.$inject = [
        'Restangular',
        '$q'
    ];

    function DefinitionService(Restangular, $q) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getDefinitions   : getDefinitions,
            getArtifactById  : getArtifactById,
            getDefinitionById: getDefinitionById,
            getTracker       : getTracker
        };

        function getDefinitions(project_id, limit, offset) {
            var data = $q.defer();

            rest.one('projects', project_id)
                .all('trafficlights_definitions')
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

        function getArtifactById(artifact_id) {
            return rest
                .one('artifacts', artifact_id)
                .get()
                .then(function(response) {
                    return response.data;
                });
        }

        function getDefinitionById(artifact_id) {
            return rest
                .one('trafficlights_definitions', artifact_id)
                .get()
                .then(function(response) {
                    return response.data;
                });
        }

        function getTracker(tracker_id) {
            return rest
                .one('trackers', tracker_id)
                .get()
                .then(function(response) {
                    return response.data;
                });
        }
    }
})();
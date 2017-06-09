(function () {
    angular
        .module('definition')
        .service('DefinitionService', DefinitionService)
        .constant('DefinitionConstants', {
            'UNCATEGORIZED': 'Uncategorized'
        });

    DefinitionService.$inject = [
        'Restangular',
        '$q',
        'DefinitionConstants'
    ];

    function DefinitionService(Restangular, $q, DefinitionConstants) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            UNCATEGORIZED     : DefinitionConstants.UNCATEGORIZED,
            getDefinitions    : getDefinitions,
            getArtifactById   : getArtifactById,
            getDefinitionById : getDefinitionById,
            getTracker        : getTracker
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
                        results: categorize(response.data),
                        total: parseInt(response.headers('X-PAGINATION-SIZE'), 10)
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function categorize(definitions) {
            return _.map(definitions, function(definition) {
                return _.merge(definition, {
                    category: definition.category || DefinitionConstants.UNCATEGORIZED
                });
            });
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

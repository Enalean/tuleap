(function () {
    angular
        .module('project')
        .service('ProjectService', ProjectService);

    ProjectService.$inject = ['Restangular', '$q'];

    function ProjectService(Restangular, $q) {
        return {
            reorderBacklog                 : reorderBacklog,
            getProjectBacklogAcceptedTypes : getProjectBacklogAcceptedTypes
        };

        function reorderBacklog(project_id, dropped_item_id, compared_to) {
            return getRest('v1').one('projects', project_id)
                .all('backlog')
                .patch({
                    order: {
                        ids         : [dropped_item_id],
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    }
                });
        }

        function getProjectBacklogAcceptedTypes(project_id) {
            var data = $q.defer(),
                /*
                 * Use a string for the limit so that the server doesn't recognise a false value
                 * and replace it with the default value. This means the response time is much faster.
                 */
                limit = '00';

            getRest('v2').one('projects', project_id)
                .one('backlog').get({
                    limit: limit,
                    offset: 0
                })
                .then(function(response) {
                    result = {
                        results: getAllowedBacklogItemTypes(response.data)
                    };
                    data.resolve(result);
                });

            return data.promise;
        }

        function getAllowedBacklogItemTypes(data) {
            var allowed_trackers = data.accept.trackers;
            var accept           = [];

            _.forEach(allowed_trackers, function(allowed_tracker) {
                accept.push(getTrackerType(allowed_tracker.id));
            });

            return accept.join('|');
        }

        function getTrackerType(tracker_id) {
            var prefix = 'trackerId';
            return prefix.concat(tracker_id);
        }

        function getRest(version) {
            return Restangular.withConfig(function(RestangularConfigurer) {
                RestangularConfigurer.setFullResponse(true);
                RestangularConfigurer.setBaseUrl('/api/'+version);
            });
        }
    }
})();


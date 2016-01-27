(function () {
    angular
        .module('project')
        .service('ProjectService', ProjectService);

    ProjectService.$inject = ['Restangular', '$q'];

    function ProjectService(Restangular, $q) {
        return {
            reorderBacklog           : reorderBacklog,
            getProject               : getProject,
            getProjectBacklog        : getProjectBacklog,
            removeAddReorderToBacklog: removeAddReorderToBacklog,
            removeAddToBacklog       : removeAddToBacklog
        };

        function reorderBacklog(project_id, dropped_item_ids, compared_to) {
            return getRest('v1').one('projects', project_id)
                .all('backlog')
                .patch({
                    order: {
                        ids        : dropped_item_ids,
                        direction  : compared_to.direction,
                        compared_to: compared_to.item_id
                    }
                });
        }

        function removeAddReorderToBacklog(milestone_id, project_id, dropped_item_ids, compared_to) {
            return getRest('v1').one('projects', project_id)
                .all('backlog')
                .patch({
                    order: {
                        ids         : dropped_item_ids,
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    },
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return {
                            id         : dropped_item_id,
                            remove_from: milestone_id
                        };
                    })
                });
        }

        function removeAddToBacklog(milestone_id, project_id, dropped_item_ids) {
            return getRest('v1').one('projects', project_id)
                .all('backlog')
                .patch({
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return {
                            id         : dropped_item_id,
                            remove_from: milestone_id
                        };
                    })
                });
        }

        function getProject(project_id) {
            return getRest('v1').one('projects', project_id).get();
        }

        function getProjectBacklog(project_id) {
            var data = $q.defer(),
                /*
                 * Use a string for the limit so that the server doesn't recognise a false value
                 * and replace it with the default value. This means the response time is much faster.
                 */
                limit = '00';

            getRest('v2').one('projects', project_id)
                .one('backlog').get({
                    limit : limit,
                    offset: 0
                })
                .then(function(response) {
                    result = {
                        allowed_backlog_item_types         : getAllowedBacklogItemTypes(response.data),
                        has_user_priority_change_permission: response.data.has_user_priority_change_permission
                    };
                    data.resolve(result);
                });

            return data.promise;
        }

        function getAllowedBacklogItemTypes(data) {
            var allowed_trackers = data.accept.trackers;
            var accepted_types = {
                content : allowed_trackers,
                toString: function() {
                    var accept = [];
                    _.forEach(this.content, function(allowed_tracker) {
                        accept.push('trackerId' + allowed_tracker.id);
                    });

                    return accept.join('|');
                }
            };

            return accepted_types;
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

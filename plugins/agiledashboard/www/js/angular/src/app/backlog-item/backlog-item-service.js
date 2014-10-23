(function () {
    angular
        .module('backlog-item')
        .service('BacklogItemService', BacklogItemService);

    BacklogItemService.$inject = ['Restangular', '$q'];

    function BacklogItemService(Restangular, $q) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getProjectBacklogItems: getProjectBacklogItems,
            getMilestoneBacklogItems: getMilestoneBacklogItems
        };

        function getProjectBacklogItems(project_id, limit, offset) {
            var data = $q.defer();

            rest.one('projects', project_id)
                .all('backlog')
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

        function getMilestoneBacklogItems(milestone_id, limit, offset) {
            var data = $q.defer();

            rest.one('milestones', milestone_id)
                .all('backlog')
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
})();
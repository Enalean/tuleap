(function () {
    angular
        .module('backlog-item')
        .service('BacklogItemService', BacklogItemService);

    BacklogItemService.$inject = ['Restangular', '$q', 'BacklogItemFactory'];

    function BacklogItemService(Restangular, $q, BacklogItemFactory) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getProjectBacklogItems          : getProjectBacklogItems,
            getMilestoneBacklogItems        : getMilestoneBacklogItems,
            getBacklogItemChildren          : getBacklogItemChildren,
            reorderBacklogItemChildren      : reorderBacklogItemChildren,
            addAndReorderBacklogItemChildren: addAndReorderBacklogItemChildren,
            addBacklogItemChildren          : addBacklogItemChildren,
            removeBacklogItemChildren       : removeBacklogItemChildren
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
                    _.forEach(response.data, augmentBacklogItem);

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
                    _.forEach(response.data, augmentBacklogItem);

                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getBacklogItemChildren(backlog_item_id, limit, offset) {
            var data = $q.defer();

            rest.one('backlog_items', backlog_item_id)
                .all('children')
                .getList({
                    limit: limit,
                    offset: offset
                })
                .then(function(response) {
                    _.forEach(response.data, augmentBacklogItem);

                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function augmentBacklogItem(data) {
            BacklogItemFactory.augment(data);
        }

        function reorderBacklogItemChildren(backlog_item_id, dropped_item_id, compared_to) {
            return rest.one('backlog_items', backlog_item_id)
                .all('children')
                .patch({
                    order: {
                        ids         : [dropped_item_id],
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    }
                });
        }

        function addAndReorderBacklogItemChildren(backlog_item_id, dropped_item_id, compared_to) {
            return rest.one('backlog_items', backlog_item_id)
                .all('children')
                .patch({
                    order: {
                        ids         : [dropped_item_id],
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    },
                    add: [dropped_item_id]
                });
        }

        function addBacklogItemChildren(backlog_item_id, dropped_item_id) {
            return rest.one('backlog_items', backlog_item_id)
                .all('children')
                .patch({
                    add: [dropped_item_id]
                });
        }

        function removeBacklogItemChildren(backlog_item_id, dropped_item_id) {
            return rest.one('backlog_items', backlog_item_id)
                .all('children')
                .patch({
                    remove: [dropped_item_id]
                });
        }
    }
})();

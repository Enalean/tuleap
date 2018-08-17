import _ from "lodash";

export default BacklogItemService;

BacklogItemService.$inject = ["Restangular", "BacklogItemFactory"];

function BacklogItemService(Restangular, BacklogItemFactory) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    var self = this;

    _.extend(self, {
        getBacklogItem: getBacklogItem,
        getProjectBacklogItems: getProjectBacklogItems,
        getMilestoneBacklogItems: getMilestoneBacklogItems,
        getBacklogItemChildren: getBacklogItemChildren,
        reorderBacklogItemChildren: reorderBacklogItemChildren,
        removeAddReorderBacklogItemChildren: removeAddReorderBacklogItemChildren,
        removeAddBacklogItemChildren: removeAddBacklogItemChildren
    });

    function getBacklogItem(backlog_item_id) {
        var promise = rest
            .one("backlog_items", backlog_item_id)
            .get()
            .then(function(response) {
                augmentBacklogItem(response.data);

                var result = {
                    backlog_item: response.data
                };

                return result;
            });

        return promise;
    }

    function getProjectBacklogItems(project_id, limit, offset) {
        var promise = rest
            .one("projects", project_id)
            .all("backlog")
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                _.forEach(response.data, augmentBacklogItem);

                var result = {
                    results: response.data,
                    total: response.headers("X-PAGINATION-SIZE")
                };

                return result;
            });

        return promise;
    }

    function getMilestoneBacklogItems(milestone_id, limit, offset) {
        var promise = rest
            .one("milestones", milestone_id)
            .all("backlog")
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                _.forEach(response.data, augmentBacklogItem);

                var result = {
                    results: response.data,
                    total: response.headers("X-PAGINATION-SIZE")
                };

                return result;
            });

        return promise;
    }

    function getBacklogItemChildren(backlog_item_id, limit, offset) {
        var promise = rest
            .one("backlog_items", backlog_item_id)
            .all("children")
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                _.forEach(response.data, augmentBacklogItem);

                var result = {
                    results: response.data,
                    total: response.headers("X-PAGINATION-SIZE")
                };

                return result;
            });

        return promise;
    }

    function augmentBacklogItem(data) {
        BacklogItemFactory.augment(data);
    }

    function reorderBacklogItemChildren(backlog_item_id, dropped_item_ids, compared_to) {
        return rest
            .one("backlog_items", backlog_item_id)
            .all("children")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id
                }
            });
    }

    function removeAddReorderBacklogItemChildren(
        source_backlog_item_id,
        dest_backlog_item_id,
        dropped_item_ids,
        compared_to
    ) {
        return rest
            .one("backlog_items", dest_backlog_item_id)
            .all("children")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id
                },
                add: _.map(dropped_item_ids, function(dropped_item_id) {
                    return {
                        id: dropped_item_id,
                        remove_from: source_backlog_item_id
                    };
                })
            });
    }

    function removeAddBacklogItemChildren(
        source_backlog_item_id,
        dest_backlog_item_id,
        dropped_item_ids
    ) {
        return rest
            .one("backlog_items", dest_backlog_item_id)
            .all("children")
            .patch({
                add: _.map(dropped_item_ids, function(dropped_item_id) {
                    return {
                        id: dropped_item_id,
                        remove_from: source_backlog_item_id
                    };
                })
            });
    }
}

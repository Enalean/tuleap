(function () {
    angular
        .module('planning')
        .service('DroppedService', DroppedService);

    DroppedService.$inject = [
        '$q',
        'ProjectService',
        'MilestoneService',
        'BacklogItemService',
        'RestErrorService'
    ];

    function DroppedService(
        $q,
        ProjectService,
        MilestoneService,
        BacklogItemService,
        RestErrorService
    ) {
        return {
            defineComparedTo                  : defineComparedTo,
            reorderBacklog                    : reorderBacklog,
            reorderSubmilestone               : reorderSubmilestone,
            reorderBacklogItemChildren        : reorderBacklogItemChildren,
            moveFromBacklogToSubmilestone     : moveFromBacklogToSubmilestone,
            moveFromChildrenToChildren        : moveFromChildrenToChildren,
            moveFromSubmilestoneToBacklog     : moveFromSubmilestoneToBacklog,
            moveFromSubmilestoneToSubmilestone: moveFromSubmilestoneToSubmilestone
        };

        function defineComparedTo(item_list, index) {
            var compared_to = {};

            if (item_list.length === 1) {
                return null;
            }

            if (index === 0) {
                compared_to.direction = 'before';
                compared_to.item_id   = item_list[index + 1].id;

                return compared_to;
            }

            compared_to.direction = 'after';
            compared_to.item_id   = item_list[index - 1].id;

            return compared_to;
        }

        function reorderBacklog(dropped_item_id, compared_to, backlog) {
            var promise;

            if (backlog.rest_base_route === 'projects' && compared_to) {
                promise = ProjectService.reorderBacklog(backlog.rest_route_id, dropped_item_id, compared_to);

            } else if (backlog.rest_base_route === 'milestones' && compared_to) {
                promise = MilestoneService.reorderBacklog(backlog.rest_route_id, dropped_item_id, compared_to);
            }

            promise = $q.when(promise);
            catchRestError(promise);

            return promise;
        }

        function reorderSubmilestone(dropped_item_id, compared_to, submilestone_id) {
            var promise = MilestoneService.reorderContent(submilestone_id, dropped_item_id, compared_to);

            catchRestError(promise);

            return promise;
        }

        function reorderBacklogItemChildren(dropped_item_id, compared_to, backlog_item_id) {
            var promise = BacklogItemService.reorderBacklogItemChildren(backlog_item_id, dropped_item_id, compared_to);

            catchRestError(promise);

            return promise;
        }

        function moveFromBacklogToSubmilestone(dropped_item_id, compared_to, submilestone_id) {
            var promise;

            if (compared_to) {
                promise = MilestoneService.addReorderToContent(submilestone_id, dropped_item_id, compared_to);
            } else {
                promise = MilestoneService.addToContent(submilestone_id, dropped_item_id);
            }

            catchRestError(promise);

            return promise;
        }

        function moveFromChildrenToChildren(dropped_item_id, compared_to, source_backlog_item_id, dest_backlog_item_id) {
            var promise;

            if (compared_to) {
                promise = BacklogItemService.removeAddReorderBacklogItemChildren(source_backlog_item_id, dest_backlog_item_id, dropped_item_id, compared_to);
            } else {
                promise = BacklogItemService.removeAddBacklogItemChildren(source_backlog_item_id, dest_backlog_item_id, dropped_item_id);
            }

            catchRestError(promise);

            return promise;
        }

        function moveFromSubmilestoneToBacklog(dropped_item_id, compared_to, submilestone_id, backlog) {
            var promise;

            if (backlog.rest_base_route === 'projects') {
                if (compared_to) {
                    promise = ProjectService.removeAddReorderToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id, compared_to);
                } else {
                    promise = ProjectService.removeAddToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id);
                }

            } else if (backlog.rest_base_route === 'milestones') {
                if (compared_to) {
                    promise = MilestoneService.removeAddReorderToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id, compared_to);
                } else {
                    promise = MilestoneService.removeAddToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id);
                }
            }

            promise = $q.when(promise);
            catchRestError(promise);

            return promise;
        }

        function moveFromSubmilestoneToSubmilestone(dropped_item_id, compared_to, source_submilestone_id, dest_submilestone_id) {
            var promise;

            if (compared_to) {
                promise = MilestoneService.removeAddReorderToContent(source_submilestone_id, dest_submilestone_id, dropped_item_id, compared_to);
            } else {
                promise = MilestoneService.removeAddToContent(source_submilestone_id, dest_submilestone_id, dropped_item_id);
            }

            catchRestError(promise);

            return promise;
        }

        function catchRestError(promise) {
            return promise.catch(function(data) {
                RestErrorService.setError(data.data.error);
            });
        }
    }
})();

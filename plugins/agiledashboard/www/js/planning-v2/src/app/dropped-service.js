(function () {
    angular
        .module('planning')
        .service('DroppedService', DroppedService);

    DroppedService.$inject = ['$q', 'ProjectService', 'MilestoneService', 'BacklogItemService'];

    function DroppedService($q, ProjectService, MilestoneService, BacklogItemService) {
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
            if (backlog.rest_base_route === 'projects' && compared_to) {
                return ProjectService.reorderBacklog(backlog.rest_route_id, dropped_item_id, compared_to);

            } else if (backlog.rest_base_route === 'milestones' && compared_to) {
                return MilestoneService.reorderBacklog(backlog.rest_route_id, dropped_item_id, compared_to);
            }
        }

        function reorderSubmilestone(dropped_item_id, compared_to, submilestone_id) {
            return MilestoneService.reorderContent(submilestone_id, dropped_item_id, compared_to);
        }

        function reorderBacklogItemChildren(dropped_item_id, compared_to, backlog_item_id) {
            return BacklogItemService.reorderBacklogItemChildren(backlog_item_id, dropped_item_id, compared_to);
        }

        function moveFromBacklogToSubmilestone(dropped_item_id, compared_to, submilestone_id) {
            if (compared_to) {
                return MilestoneService.addReorderToContent(submilestone_id, dropped_item_id, compared_to);
            } else {
                return MilestoneService.addToContent(submilestone_id, dropped_item_id);
            }
        }

        function moveFromChildrenToChildren(dropped_item_id, compared_to, source_backlog_item_id, dest_backlog_item_id) {
            if (compared_to) {
                return BacklogItemService.removeAddReorderBacklogItemChildren(source_backlog_item_id, dest_backlog_item_id, dropped_item_id, compared_to);
            } else {
                return BacklogItemService.removeAddBacklogItemChildren(source_backlog_item_id, dest_backlog_item_id, dropped_item_id);
            }
        }

        function moveFromSubmilestoneToBacklog(dropped_item_id, compared_to, submilestone_id, backlog) {
            if (backlog.rest_base_route === 'projects') {
                if (compared_to) {
                    return ProjectService.removeAddReorderToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id, compared_to);
                } else {
                    return ProjectService.removeAddToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id);
                }

            } else if (backlog.rest_base_route === 'milestones') {
                if (compared_to) {
                    return MilestoneService.removeAddReorderToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id, compared_to);
                } else {
                    return MilestoneService.removeAddToBacklog(submilestone_id, backlog.rest_route_id, dropped_item_id);
                }
            }
        }

        function moveFromSubmilestoneToSubmilestone(dropped_item_id, compared_to, source_submilestone_id, dest_submilestone_id) {
            if (compared_to) {
                return MilestoneService.removeAddReorderToContent(source_submilestone_id, dest_submilestone_id, dropped_item_id, compared_to);
            } else {
                return MilestoneService.removeAddToContent(source_submilestone_id, dest_submilestone_id, dropped_item_id);
            }
        }
    }
})();

(function () {
    angular
        .module('planning')
        .service('DroppedService', DroppedService);

    DroppedService.$inject = ['$q', 'ProjectService', 'MilestoneService', 'BacklogItemService'];

    function DroppedService($q, ProjectService, MilestoneService, BacklogItemService) {
        return {
            defineComparedTo                   : defineComparedTo,
            reorderBacklog                     : reorderBacklog,
            reorderSubmilestone                : reorderSubmilestone,
            reorderBacklogItemChildren         : reorderBacklogItemChildren,
            moveFromBacklogToSubmilestone      : moveFromBacklogToSubmilestone,
            moveFromChildrenToChildren         : moveFromChildrenToChildren,
            moveFromSubmilestoneToBacklog      : moveFromSubmilestoneToBacklog,
            moveFromSubmilestoneToSubmilestone : moveFromSubmilestoneToSubmilestone
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
            return MilestoneService.addAndReorderToContent(submilestone_id, dropped_item_id, compared_to);
        }

        function moveFromChildrenToChildren(dropped_item_id, compared_to, source_backlog_item_id, dest_backlog_item_id) {
            var data = $q.defer();

            BacklogItemService.removeBacklogItemChildren(source_backlog_item_id, dropped_item_id).then(
                function() {
                    if (compared_to) {
                        BacklogItemService.addAndReorderBacklogItemChildren(dest_backlog_item_id, dropped_item_id, compared_to).then(wentFine, errorOccured);
                    } else {
                        BacklogItemService.addBacklogItemChildren(dest_backlog_item_id, dropped_item_id).then(wentFine, errorOccured);
                    }
                }, errorOccured
            );

            return data.promise;

            function wentFine() {
                var error_occured = false;
                data.resolve(error_occured);
            }

            function errorOccured() {
                var error_occured = true;
                data.resolve(error_occured);
            }
        }

        function moveFromSubmilestoneToBacklog(dropped_item_id, compared_to, submilestone_id, backlog) {
            var data = $q.defer();

            MilestoneService.removeFromContent(submilestone_id, dropped_item_id).then(
                function() {
                    if (backlog.rest_base_route === 'projects') {
                        if (compared_to) {
                            ProjectService.reorderBacklog(backlog.rest_route_id, dropped_item_id, compared_to).then(wentFine, errorOccured);
                        }
                        // no add to project's backlog, it's normal

                    } else if (backlog.rest_base_route === 'milestones') {
                        if (compared_to) {
                            MilestoneService.addAndReorderToBacklog(backlog.rest_route_id, dropped_item_id, compared_to).then(wentFine, errorOccured);
                        } else {
                            MilestoneService.addToBacklog(backlog.rest_route_id, dropped_item_id).then(wentFine, errorOccured);
                        }
                    }

                }, errorOccured
            );

            return data.promise;

            function wentFine() {
                var error_occured = false;
                data.resolve(error_occured);
            }

            function errorOccured() {
                var error_occured = true;
                data.resolve(error_occured);
            }
        }

        function moveFromSubmilestoneToSubmilestone(dropped_item_id, compared_to, source_submilestone_id, dest_submilestone_id) {
            var data = $q.defer();

            MilestoneService.removeFromContent(source_submilestone_id, dropped_item_id).then(
                function() {
                    if (compared_to) {
                        MilestoneService.addAndReorderToContent(dest_submilestone_id, dropped_item_id, compared_to).then(wentFine, errorOccured);
                    } else {
                        MilestoneService.addToContent(dest_submilestone_id, dropped_item_id).then(wentFine, errorOccured);
                    }
                }, errorOccured
            );

            return data.promise;

            function wentFine() {
                var error_occured = false;
                data.resolve(error_occured);
            }

            function errorOccured() {
                var error_occured = true;
                data.resolve(error_occured);
            }
        }
    }
})();
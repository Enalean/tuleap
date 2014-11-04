(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = ['$scope', 'SharedPropertiesService', 'BacklogItemService', 'MilestoneService'];

    function PlanningCtrl($scope, SharedPropertiesService, BacklogItemService, MilestoneService) {
        var project_id                  = SharedPropertiesService.getProjectId(),
            milestone_id                = SharedPropertiesService.getMilestoneId(),
            pagination_limit            = 50,
            pagination_offset           = 0,
            show_closed_milestone_items = true;

        _.extend($scope, {
            backlog_items              : [],
            milestones                 : [],
            loading_backlog_items      : true,
            loading_milestones         : true,
            toggle                     : toggle,
            showChildren               : showChildren,
            toggleClosedMilestoneItems : toggleClosedMilestoneItems,
            canShowBacklogItem         : canShowBacklogItem
        });

        displayBacklogItems();
        displayMilestones();

        function displayBacklogItems() {
            if (typeof milestone_id === 'undefined') {
                fetchProjectBacklogItems(project_id, pagination_limit, pagination_offset);
            } else {
                fetchMilestoneBacklogItems(milestone_id, pagination_limit, pagination_offset);
            }
        }

        function fetchProjectBacklogItems(project_id, limit, offset) {
            return BacklogItemService.getProjectBacklogItems(project_id, limit, offset).then(function(data) {
                $scope.backlog_items = $scope.backlog_items.concat(data.results);

                if ($scope.backlog_items.length < data.total) {
                    fetchProjectBacklogItems(project_id, limit, offset + limit);
                } else {
                    $scope.loading_backlog_items = false;
                }
            });
        }

        function fetchMilestoneBacklogItems(milestone_id, limit, offset) {
            return BacklogItemService.getMilestoneBacklogItems(milestone_id, limit, offset).then(function(data) {
                $scope.backlog_items = $scope.backlog_items.concat(data.results);

                if ($scope.backlog_items.length < data.total) {
                    fetchMilestoneBacklogItems(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_backlog_items = false;
                }
            });
        }

        function displayMilestones() {
            if (typeof milestone_id === 'undefined') {
                fetchMilestones(project_id, pagination_limit, pagination_offset);
            } else {
                fetchSubMilestones(milestone_id, pagination_limit, pagination_offset);
            }
        }

        function fetchMilestones(project_id, limit, offset) {
            return MilestoneService.getMilestones(project_id, limit, offset).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ($scope.milestones.length < data.total) {
                    fetchMilestones(project_id, limit, offset + limit);
                } else {
                    $scope.loading_milestones = false;
                }
            });
        }

        function fetchSubMilestones(milestone_id, limit, offset) {
            return MilestoneService.getSubMilestones(milestone_id, limit, offset).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ($scope.milestones.length < data.total) {
                    fetchSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_milestones = false;
                }
            });
        }

        function toggle(milestone) {
            if (! milestone.alreadyLoaded && milestone.content.length === 0) {
                milestone.getContent();
            }

            if (milestone.collapsed) {
                return milestone.collapsed = false;
            }

            return milestone.collapsed = true;
        }

        function showChildren(backlog_item) {
            backlog_item.are_children_shown = ! backlog_item.are_children_shown;

            if (typeof backlog_item.children === 'undefined') {
                backlog_item.loading  = true;
                backlog_item.children = [];
                fetchBacklogItemChildren(backlog_item, pagination_limit, pagination_offset);
            }
        }

        function fetchBacklogItemChildren(backlog_item, limit, offset) {
            return BacklogItemService.getBacklogItemChildren(backlog_item.id, limit, offset).then(function(data) {
                backlog_item.children = backlog_item.children.concat(data.results);

                if (backlog_item.children.length < data.total) {
                    fetchBacklogItemChildren(backlog_item, limit, offset + limit);
                } else {
                    backlog_item.loading = false;
                }
            });
        }

        function toggleClosedMilestoneItems() {
            show_closed_milestone_items = (show_closed_milestone_items === true) ? false : true;
        }

        function canShowBacklogItem(backlog_item) {
            return backlog_item.isOpen() || show_closed_milestone_items;
        }
    }
})();

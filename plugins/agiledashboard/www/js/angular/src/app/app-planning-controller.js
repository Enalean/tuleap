(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = ['$scope', 'SharedPropertiesService', 'BacklogItemService'];

    function PlanningCtrl($scope, SharedPropertiesService, BacklogItemService) {
        var project_id   = SharedPropertiesService.getProjectId(),
            milestone_id = SharedPropertiesService.getMilestoneId();

        _.extend($scope, {
            backlog_items:          [],
            nb_total_backlog_items: 0,
            loading_backlog_items:  true
        });

        getBacklogItems();

        function getBacklogItems() {
            if (typeof milestone_id === 'undefined') {
                return fetchProjectBacklogItems(project_id, 50, 0);
            }

            return fetchMilestoneBacklogItems(milestone_id, 50, 0);
        }

        function fetchProjectBacklogItems(project_id, limit, offset) {
            BacklogItemService.getProjectBacklogItems(project_id, limit, offset).then(function(data) {
                $scope.backlog_items          = $scope.backlog_items.concat(data.results);
                $scope.nb_total_backlog_items = data.total;

                if ($scope.backlog_items.length < $scope.nb_total_backlog_items) {
                    fetchProjectBacklogItems(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_backlog_items = false;
                }
            });
        }

        function fetchMilestoneBacklogItems(milestone_id, limit, offset) {
            BacklogItemService.getMilestoneBacklogItems(milestone_id, limit, offset).then(function(data) {
                $scope.backlog_items          = $scope.backlog_items.concat(data.results);
                $scope.nb_total_backlog_items = data.total;

                if ($scope.backlog_items.length < $scope.nb_total_backlog_items) {
                    fetchMilestoneBacklogItems(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_backlog_items = false;
                }
            });
        }
    }
})();
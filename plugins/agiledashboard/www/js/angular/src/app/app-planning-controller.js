(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = ['$scope', 'SharedPropertiesService', 'BacklogItemService', 'MilestoneService'];

    function PlanningCtrl($scope, SharedPropertiesService, BacklogItemService, MilestoneService) {
        var project_id          = SharedPropertiesService.getProjectId(),
            milestone_id        = SharedPropertiesService.getMilestoneId();

        _.extend($scope, {
            backlog_items         : [],
            milestones            : [],
            loading_backlog_items : true,
            loading_milestones    : true
        });

        displayBacklogItems();
        displayMilestones();

        function displayBacklogItems() {
            var fetch;

            if (typeof milestone_id === 'undefined') {
                fetch = fetchProjectBacklogItems(project_id, 50, 0);
            } else {
                fetch = fetchMilestoneBacklogItems(milestone_id, 50, 0);
            }

            fetch.then(function(){
                $scope.loading_backlog_items = false;
            });
        }

        function fetchProjectBacklogItems(project_id, limit, offset) {
            return BacklogItemService.getProjectBacklogItems(project_id, limit, offset).then(function(data) {
                $scope.backlog_items = $scope.backlog_items.concat(data.results);

                if ($scope.backlog_items.length < data.total) {
                    fetchProjectBacklogItems(milestone_id, limit, offset + limit);
                }
            });
        }

        function fetchMilestoneBacklogItems(milestone_id, limit, offset) {
            return BacklogItemService.getMilestoneBacklogItems(milestone_id, limit, offset).then(function(data) {
                $scope.backlog_items = $scope.backlog_items.concat(data.results);

                if ($scope.backlog_items.length < data.total) {
                    fetchMilestoneBacklogItems(milestone_id, limit, offset + limit);
                }
            });
        }

        function displayMilestones() {
            var fetch;

            if (typeof milestone_id === 'undefined') {
                fetch = fetchMilestones(project_id, 50, 0);
            } else {
                fetch = fetchSubMilestones(milestone_id, 50, 0);
            }

            fetch.then(function(){
                $scope.loading_milestones = false;
            });
        }

        function fetchMilestones(project_id, limit, offset) {
            return MilestoneService.getMilestones(project_id, limit, offset).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ($scope.milestones.length < data.total) {
                    fetchMilestones(project_id, limit, offset + limit);
                }
            });
        }

        function fetchSubMilestones(milestone_id, limit, offset) {
            return MilestoneService.getSubMilestones(milestone_id, limit, offset).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ($scope.milestones.length < data.total) {
                    fetchSubMilestones(milestone_id, limit, offset + limit);
                }
            });
        }
    }
})();

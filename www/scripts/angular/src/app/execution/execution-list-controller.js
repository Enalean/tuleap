angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = ['$scope', '$state', 'ExecutionService', 'CampaignService'];

function ExecutionListCtrl($scope, $state, ExecutionService, CampaignService) {
    var campaign_id = $state.params.id;

    ExecutionService.loadExecutions(campaign_id);

    $scope.campaign             = CampaignService.getCampaign(campaign_id);
    $scope.categories           = ExecutionService.executions_by_categories_by_campaigns[campaign_id];
    $scope.environments         = [];
    $scope.assignees            = [];
    $scope.search               = '';
    $scope.selected_assignee    = null;
    $scope.selected_environment = null;
    $scope.loading              = loading;
    $scope.status               = {
        passed:  false,
        failed:  false,
        blocked: false,
        notrun:  false
    };

    getAssignees(campaign_id, 50, 0);
    getEnvironments(campaign_id, 50, 0);

    function getEnvironments(campaign_id, limit, offset) {
        CampaignService.getEnvironments(campaign_id, limit, offset).then(function(data) {
            $scope.environments = $scope.environments.concat(data.results);

            if ($scope.environments.length < data.total) {
                return getEnvironments(campaign_id, limit, offset + limit);
            }
        });
    }

    function getAssignees(campaign_id, limit, offset) {
        CampaignService.getAssignees(campaign_id, limit, offset).then(function(data) {
            $scope.assignees = $scope.assignees.concat(data.results);

            if ($scope.assignees.length < data.total) {
                return getAssignees(campaign_id, limit, offset + limit);
            }
        });
    }

    function loading() {
        return ExecutionService.loading[campaign_id] === true;
    }
}
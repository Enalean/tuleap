angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = ['$scope', '$state', '$sce', 'executions', 'environments', 'assignees', 'CampaignService'];

function ExecutionListCtrl($scope, $state, $sce, executions, environments, assignees, CampaignService) {
    var campaign_id           = $state.params.id,
        total_assignees       = 0,
        total_environments    = 0;

    $scope.campaign             = CampaignService.getCampaign(campaign_id);
    $scope.categories           = executions;
    $scope.environments         = environments;
    $scope.assignees            = assignees;
    $scope.search               = '';
    $scope.selected_assignee    = null;
    $scope.selected_environment = null;
    $scope.status               = {
        passed:  false,
        failed:  false,
        blocked: false,
        notrun:  false
    };
    $scope.getExecutionTitle = getExecutionTitle;
    $scope.sanitizeHtml      = sanitizeHtml;

    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function getExecutionTitle(execution) {
        return '#' + execution.definition.id + ' ' + sanitizeHtml(execution.definition.summary);
    }
}
angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = ['$scope', '$state', 'ExecutionService', 'CampaignService'];

function ExecutionListCtrl($scope, $state, ExecutionService, CampaignService) {
    var campaign_id     = $state.params.id,
        executions      = ExecutionService.getExecutions(campaign_id),
        total_assignees = 0;

    $scope.campaign          = CampaignService.getCampaign(campaign_id);
    $scope.categories        = groupExecutionsByCategory(executions);
    $scope.assignees         = getAssignees(campaign_id, 50, 0);
    $scope.search            = '';
    $scope.selected_assignee = null;
    $scope.status            = {
        passed:  false,
        failed:  false,
        blocked: false,
        notrun:  false
    };

    function groupExecutionsByCategory(executions) {
        var categories = {};

        executions.forEach(function(execution) {
            if (typeof categories[execution.test_def.category] === "undefined") {
                categories[execution.test_def.category] = {
                    label     : execution.test_def.category,
                    executions: []
                };
            }

            categories[execution.test_def.category].executions.push(execution);
        });

        return categories;
    }

    function getAssignees(campaign_id, limit, offset) {
        CampaignService.getAssignees(campaign_id, limit, offset).then(function(data) {
            $scope.assignees = $scope.assignees.concat(data.results);
            total_assignees  = data.total;

            if ($scope.assignees.length < total_assignees) {
                getAssignees(campaign_id, limit, offset + limit);
            }
        });

        return [];
    }
}
angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = ['$scope', '$state', 'ExecutionService', 'CampaignService'];

function ExecutionListCtrl($scope, $state, ExecutionService, CampaignService) {
    var campaign_id      = $state.params.id,
        executions       = [],
        total_executions = 0,
        total_assignees  = 0;

    $scope.campaign          = CampaignService.getCampaign(campaign_id);
    $scope.categories        = {};
    $scope.assignees         = [];
    $scope.search            = '';
    $scope.selected_assignee = null;
    $scope.status            = {
        passed:  false,
        failed:  false,
        blocked: false,
        notrun:  false
    };

    getAssignees(campaign_id, 50, 0);
    getExecutions(campaign_id, 50, 0);

    function getAssignees(campaign_id, limit, offset) {
        CampaignService.getAssignees(campaign_id, limit, offset).then(function(data) {
            $scope.assignees = $scope.assignees.concat(data.results);
            total_assignees  = data.total;

            if ($scope.assignees.length < total_assignees) {
                getAssignees(campaign_id, limit, offset + limit);
            }
        });
    }

    function getExecutions(campaign_id, limit, offset) {
        ExecutionService.getExecutions(campaign_id, limit, offset).then(function(data) {
            executions        = executions.concat(data.results);
            total_executions  = data.total;

            groupExecutionsByCategory(data.results);
            console.log($scope.categories);

            if (executions.length < total_executions) {
                getExecutions(campaign_id, limit, offset + limit);
            }
        });
    }

    function groupExecutionsByCategory(executions) {
        executions.forEach(function(execution) {
            if (typeof $scope.categories[execution.test_definition.category] === "undefined") {
                $scope.categories[execution.test_definition.category] = {
                    label     : execution.test_definition.category,
                    executions: []
                };
            }

            $scope.categories[execution.test_definition.category].executions.push(execution);
        });
    }
}
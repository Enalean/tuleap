angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = ['$scope', '$state', '$sce', 'ExecutionService', 'CampaignService'];

function ExecutionListCtrl($scope, $state, $sce, ExecutionService, CampaignService) {
    var campaign_id         = $state.params.id,
        executions          = [],
        total_executions    = 0,
        total_assignees     = 0;
        total_environments  = 0;

    $scope.campaign             = CampaignService.getCampaign(campaign_id);
    $scope.categories           = {};
    $scope.environments         = [];
    $scope.assignees            = [];
    $scope.search               = '';
    $scope.selected_assignee    = null;
    $scope.selected_environment = null;
    $scope.status               = {
        passed:  false,
        failed:  false,
        blocked: false,
        notrun:  false
    };
    $scope.current_execution    = null;
    $scope.openExecution        = openExecution;
    $scope.isExecutionActive    = isExecutionActive;
    $scope.getExecutionTitle    = getExecutionTitle;
    $scope.sanitizeHtml         = sanitizeHtml;

    getEnvironments(campaign_id, 50, 0);
    getAssignees(campaign_id, 50, 0);
    getExecutions(campaign_id, 50, 0);


    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function isExecutionActive(execution) {
        if ($scope.current_execution) {
            return execution.id === $scope.current_execution.id;
        }

        return false;
    }

    function getExecutionTitle(execution) {
        return '#' + execution.definition.id + ' ' + sanitizeHtml(execution.definition.summary);
    }

    function getEnvironments(campaign_id, limit, offset) {
        CampaignService.getEnvironments(campaign_id, limit, offset).then(function(data) {
            $scope.environments = $scope.environments.concat(data.results);
            total_environments  = data.total;

            if ($scope.environments.length < total_environments) {
                getEnvironments(campaign_id, limit, offset + limit);
            }
        });
    }

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

            if (executions.length < total_executions) {
                getExecutions(campaign_id, limit, offset + limit);
            }
        });
    }

    function groupExecutionsByCategory(executions) {
        executions.forEach(function(execution) {
            var category = execution.definition.category;
            if (! category) {
                category = 'Uncategorized';
                execution.definition._uncategorized = category;
            }

            if (typeof $scope.categories[category] === "undefined") {
                $scope.categories[category] = {
                    label     : category,
                    executions: []
                };
            }

            $scope.categories[category].executions.push(execution);
        });
    }

    function openExecution(execution) {
        $scope.current_execution = execution;
    }
}
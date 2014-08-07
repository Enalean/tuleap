angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = ['$scope', '$state', 'ExecutionService'];

function ExecutionListCtrl($scope, $state, ExecutionService) {
    var campaign_id = $state.params.id,
        executions  = ExecutionService.getExecutions(campaign_id);

    $scope.categories = groupExecutionsByCategory(executions);

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
}
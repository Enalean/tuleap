angular
    .module('execution')
    .controller('ExecutionDetailCtrl', ExecutionDetailCtrl);

ExecutionDetailCtrl.$inject = ['$scope', '$state', '$sce', 'executions', 'ExecutionService'];

function ExecutionDetailCtrl($scope, $state, $sce, executions, ExecutionService) {
    var definition_id = +$state.params.defid;

    $scope.execution = _.find(_.flatten(executions, 'executions'), function (execution) {
        return execution.definition.id === definition_id;
    });

    $scope.sanitizeHtml      = sanitizeHtml;
    $scope.pass              = pass;
    $scope.fail              = fail;
    $scope.block             = block;

    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function pass(execution) {
        execution.status = "passed";
        ExecutionService.putExecution(execution);
    }

    function fail(execution) {
        execution.status = "failed";
        ExecutionService.putExecution(execution);
    }

    function block(execution) {
        execution.status = "blocked";
        ExecutionService.putExecution(execution);
    }
}
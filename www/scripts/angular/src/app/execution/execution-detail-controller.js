angular
    .module('execution')
    .controller('ExecutionDetailCtrl', ExecutionDetailCtrl);

ExecutionDetailCtrl.$inject = ['$scope', '$state', '$sce', 'executions', 'ExecutionService', 'SharedPropertiesService'];

function ExecutionDetailCtrl($scope, $state, $sce, executions, ExecutionService, SharedPropertiesService) {
    var definition_id = +$state.params.defid;

    $scope.execution = _.find(_.flatten(executions, 'executions'), function (execution) {
        return execution.definition.id === definition_id;
    });

    $scope.sanitizeHtml = sanitizeHtml;
    $scope.pass         = pass;
    $scope.fail         = fail;
    $scope.block        = block;

    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function pass(execution) {
        setNewStatus(execution, "passed");
    }

    function fail(execution) {
        setNewStatus(execution, "failed");
    }

    function block(execution) {
        setNewStatus(execution, "blocked");
    }

    function setNewStatus(execution, new_status) {
        var previous_status = execution.status;

        execution.status = new_status;
        ExecutionService.putExecution(execution).then(function () {
            execution.previous_result.status       = previous_status;
            execution.previous_result.submitted_on = new Date();
            execution.previous_result.submitted_by = SharedPropertiesService.getCurrentUser();
        });
    }
}
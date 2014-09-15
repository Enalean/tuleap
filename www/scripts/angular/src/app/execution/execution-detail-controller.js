angular
    .module('execution')
    .controller('ExecutionDetailCtrl', ExecutionDetailCtrl);

ExecutionDetailCtrl.$inject = ['$scope', '$state', '$sce', 'gettextCatalog', 'executions', 'ExecutionService', 'SharedPropertiesService'];

function ExecutionDetailCtrl($scope, $state, $sce, gettextCatalog, executions, ExecutionService, SharedPropertiesService) {
    var execution_id = +$state.params.execid;

    $scope.execution = _.find(_.flatten(executions, 'executions'), function (execution) {
        return execution.id === execution_id;
    });
    $scope.execution.results = '';
    $scope.execution.saving  = false;
    $scope.error_message     = '';
    $scope.pass              = pass;
    $scope.fail              = fail;
    $scope.block             = block;
    $scope.sanitizeHtml      = sanitizeHtml;
    $scope.getStatusLabel    = getStatusLabel;

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
        var previous_status   = execution.status,
            execution_to_save = angular.copy(execution);

        execution_to_save.status = new_status;

        execution.saving = true;

        ExecutionService.putExecution(execution_to_save).then(function() {
            execution.status = new_status;
            execution.previous_result.status       = previous_status;
            execution.previous_result.submitted_on = new Date();
            execution.previous_result.submitted_by = SharedPropertiesService.getCurrentUser();
            execution.previous_result.result       = execution.results;
            execution.results                      = '';
            $scope.error_message                   = '';

            execution.saving = false;

        }, function() {
            $scope.error_message = gettextCatalog.getString('An error has occured. Please contact an administrator.');

            execution.saving = false;
        });
    }

    function getStatusLabel(status) {
        var labels = {
            passed: 'Passed',
            failed: 'Failed',
            blocked: 'Blocked',
            notrun: 'Not Run'
        };

        return labels[status];
    }
}
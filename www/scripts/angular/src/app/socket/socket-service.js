(function () {
    angular
        .module('socket')
        .service('SocketService', SocketService);

    SocketService.$inject = ['SocketFactory', 'SharedPropertiesService', 'ExecutionService', 'ngAudio'];

    function SocketService(SocketFactory, SharedPropertiesService, ExecutionService, ngAudio) {
        return {
            viewTestExecution: viewTestExecution,
            listenToExecutionViewed: listenToExecutionViewed,
            updateTestExecution: updateTestExecution,
            listenToExecutionUpdated: listenToExecutionUpdated
        };

        function prepareData(data) {
            var current_user = SharedPropertiesService.getCurrentUser();

            return {
                project_id: SharedPropertiesService.getProjectId(),
                user_id: current_user.id,
                token: current_user.token,
                data: data
            };
        }

        function viewTestExecution(data) {
            SocketFactory.emit('test_execution:view', prepareData(data));
        }

        function listenToExecutionViewed(execution) {
            SocketFactory.on('test_execution:view', function(response) {
                if (typeof ExecutionService.executions[response.data.id] !== 'undefined') {
                    ExecutionService.executions[response.data.id].viewed_by = response.data.user;
                }
            });
        }

        function updateTestExecution(execution) {
            SocketFactory.emit('test_execution:update', prepareData(execution));
        }

        function listenToExecutionUpdated() {
            SocketFactory.on('test_execution:update', function(response) {
                if (response.status !== 200) {
                    ExecutionService.executions[response.data.id].saving = false;
                    ExecutionService.executions[response.data.id].error  = response.status + ' - ' + JSON.parse(response.message).error.message;

                } else {
                    var execution       = ExecutionService.executions[response.data.id];
                    var previous_status = execution.status;

                    response.data.saving = false;
                    _.assign(execution, response.data);

                    execution.previous_result.status       = previous_status;
                    execution.previous_result.submitted_on = new Date();
                    execution.previous_result.submitted_by = execution.submitted_by;
                    execution.previous_result.result       = execution.results;
                    execution.submitted_by                 = null;
                    execution.results                      = '';
                    execution.error                        = '';

                    if (execution.previous_result.submitted_by.id !== SharedPropertiesService.getCurrentUser().id) {
                        switch (response.data.status) {
                            case 'failed':
                                ngAudio.play('sound-failed');
                                break;
                            case 'passed':
                                ngAudio.play('sound-passed');
                                break;
                            case 'blocked':
                                ngAudio.play('sound-blocked');
                                break;
                        }
                    }
                }
            });
        }
    }
})();

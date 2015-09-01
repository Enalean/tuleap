angular
    .module('socket')
    .service('SocketService', SocketService);

SocketService.$inject = ['SocketFactory', 'SharedPropertiesService', 'ExecutionService'];

function SocketService(SocketFactory, SharedPropertiesService, ExecutionService) {
    return {
        viewTestExecution       : viewTestExecution,
        listenToExecutionViewed : listenToExecutionViewed,
        updateTestExecution     : updateTestExecution,
        listenToExecutionUpdated: listenToExecutionUpdated,
        getGlobalPositions      : getGlobalPositions
    };

    function prepareData(data) {
        var current_user = SharedPropertiesService.getCurrentUser();

        return {
            project_id: SharedPropertiesService.getProjectId(),
            user_id   : current_user.id,
            token     : current_user.token,
            data      : data
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

    function getGlobalPositions() {
        SocketFactory.on('positions:all', function(response) {
            response.forEach(function(element) {
                ExecutionService.executions[element.id].viewed_by = element.user;
            });
        });

        SocketFactory.emit('positions:all', prepareData());
    }

    function updateTestExecution(execution) {
        SocketFactory.emit('test_execution:update', prepareData(execution));
    }

    function listenToExecutionUpdated(campaign) {
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

                switch (execution.status) {
                    case 'passed':
                        campaign.nb_of_passed++;
                        break;
                    case 'failed':
                        campaign.nb_of_failed++;
                        break;
                    case 'blocked':
                        campaign.nb_of_blocked++;
                        break;
                }

                switch (previous_status) {
                    case 'passed':
                        campaign.nb_of_passed--;
                        break;
                    case 'failed':
                        campaign.nb_of_failed--;
                        break;
                    case 'blocked':
                        campaign.nb_of_blocked--;
                        break;
                    default:
                        campaign.nb_of_not_run--;
                        break;
                }
            }
        });
    }
}

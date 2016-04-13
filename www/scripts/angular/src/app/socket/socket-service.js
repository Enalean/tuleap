angular
    .module('socket')
    .service('SocketService', SocketService);

SocketService.$inject = [
    '$q',
    'Restangular',
    'SocketFactory',
    'ExecutionService'
];

function SocketService(
    $q,
    Restangular,
    SocketFactory,
    ExecutionService
) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        listenNodeJSServer      : listenNodeJSServer,
        listenToExecutionViewed : listenToExecutionViewed,
        listenToExecutionLeft   : listenToExecutionLeft,
        listenToExecutionUpdated: listenToExecutionUpdated
    };

    function listenNodeJSServer() {
        if (! _.isEmpty(SocketFactory) && ! _.has(SocketFactory, 'on')) {
            return SocketFactory.initialization().then(function (response) {
                SocketFactory = response;
                return SocketFactory;
            });
        } else {
            return $q.reject();
        }
    }

    function listenToExecutionViewed() {
        SocketFactory.on('trafficlights_user:presence', function(data) {
            if (_.has(data, 'execution_to_remove')) {
                ExecutionService.removeViewTestExecution(data.execution_to_remove, data.user);
            }
            if (_.has(data, 'execution_to_add')) {
                ExecutionService.viewTestExecution(data.execution_to_add, data.user);
            }
        });
    }

    function listenToExecutionLeft() {
        SocketFactory.on('user:leave', function(uuid) {
            ExecutionService.removeViewTestExecutionByUUID(uuid);
        });
    }

    function listenToExecutionUpdated() {
        SocketFactory.on('trafficlights_execution:update', function(response) {
            ExecutionService.updateTestExecution(response.artifact);
        });
    }
}
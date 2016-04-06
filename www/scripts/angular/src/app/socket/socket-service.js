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

    function listenToExecutionViewed(execution) {
        SocketFactory.on('test_execution:view', function(response) {
            if (typeof ExecutionService.executions[response.data.id] !== 'undefined') {
                ExecutionService.executions[response.data.id].viewed_by = response.data.user;
            }
        });
    }

    function listenToExecutionUpdated() {
        SocketFactory.on('test_execution:update', function(response) {
            ExecutionService.update(response);
        });
    }
}

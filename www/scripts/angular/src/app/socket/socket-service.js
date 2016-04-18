angular
    .module('socket')
    .service('SocketService', SocketService);

SocketService.$inject = [
    '$q',
    '$rootScope',
    'locker',
    'SocketFactory',
    'ExecutionService',
    'SharedPropertiesService',
    'JWTService'
];

function SocketService(
    $q,
    $rootScope,
    locker,
    SocketFactory,
    ExecutionService,
    SharedPropertiesService,
    JWTService
) {
    return {
        listenNodeJSServer      : listenNodeJSServer,
        listenToExecutionViewed : listenToExecutionViewed,
        listenToExecutionLeft   : listenToExecutionLeft,
        listenToExecutionUpdated: listenToExecutionUpdated
    };

    function listenNodeJSServer() {
        if (SharedPropertiesService.getNodeServerAddress()) {
            listenToError();
            listenPresences();
            return JWTService.getJWT().then(function (data) {
                locker.put('token', data.token);
                return subscribe();
            });
        } else {
            return $q.reject('No server Node.js.');
        }
    }

    function subscribe() {
        SocketFactory.emit('subscription', {
            nodejs_server_version: SharedPropertiesService.getNodeServerVersion(),
            token                : locker.get('token'),
            room_id              : 'trafficlights_' + SharedPropertiesService.getCampaignId(),
            uuid                 : SharedPropertiesService.getUUID()
        });
    }

    function listenToError() {
        SocketFactory.on('error-jwt', function(error) {
            if(error === 'JWTExpired') {
                JWTService.getJWT().then(function (data) {
                    locker.put('token', data.token);
                    subscribe();
                    ExecutionService.initialization();
                    $rootScope.$broadcast('controller-reload');
                });
            }
        });
    }

    function listenPresences() {
        SocketFactory.on('presences', function(presences) {
            ExecutionService.presences_loaded = true;
            ExecutionService.presences        = presences;
            ExecutionService.displayPresencesOnExecution(presences);
        });
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
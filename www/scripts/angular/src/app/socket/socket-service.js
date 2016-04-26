angular
    .module('socket')
    .service('SocketService', SocketService);

SocketService.$inject = [
    '$timeout',
    '$q',
    '$rootScope',
    'moment',
    'locker',
    'SocketFactory',
    'ExecutionService',
    'SharedPropertiesService',
    'JWTService'
];

function SocketService(
    $timeout,
    $q,
    $rootScope,
    moment,
    locker,
    SocketFactory,
    ExecutionService,
    SharedPropertiesService,
    JWTService
) {
    return {
        listenTokenExpired      : listenTokenExpired,
        listenNodeJSServer      : listenNodeJSServer,
        listenToExecutionViewed : listenToExecutionViewed,
        listenToExecutionLeft   : listenToExecutionLeft,
        listenToExecutionUpdated: listenToExecutionUpdated,
        refreshToken            : refreshToken
    };

    function listenTokenExpired() {
        var expired_date = moment(locker.get('token-expired-date')).subtract(5, 'm');
        var timeout      = expired_date.diff(moment());
        if (timeout < 0) {
            requestJWTToRefreshToken();
        } else {
            $timeout(function () {
                requestJWTToRefreshToken();
            }, timeout);
        }
    }

    function listenNodeJSServer() {
        if (SharedPropertiesService.getNodeServerAddress()) {
            listenToError();
            listenPresences();
            return JWTService.getJWT().then(function (data) {
                locker.put('token', data.token);
                locker.put('token-expired-date', JWTService.getTokenExpiredDate(data.token));
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

    function refreshToken() {
        SocketFactory.emit('token', {
            token: locker.get('token')
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
            ExecutionService.presences_loaded       = true;
            ExecutionService.presences_by_execution = presences;
            ExecutionService.displayPresencesOnExecution();
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

    function requestJWTToRefreshToken() {
        JWTService.getJWT().then(function (data) {
            locker.put('token', data.token);
            locker.put('token-expired-date', JWTService.getTokenExpiredDate(data.token));
            refreshToken();
            listenTokenExpired();
        });
    }
}
angular
    .module('socket')
    .service('SocketFactory', SocketFactory);

SocketFactory.$inject = [
    '$q',
    '$rootScope',
    'socketFactory',
    'SharedPropertiesService',
    'locker',
    'JWTService'
];

function SocketFactory(
    $q,
    $rootScope,
    socketFactory,
    SharedPropertiesService,
    locker,
    JWTService
) {

    return {
        initialization: initialization
    };

    function initialization() {
        if (SharedPropertiesService.getNodeServerAddress()) {
            return JWTService.getJWT().then(function (data) {
                locker.put('token', data.token);
                return createSocket(data.token);
            });
        } else {
            return $q.reject('No server Node.js.');
        }
    }

    function createSocket(token) {
        var io_socket = io.connect('https://' + SharedPropertiesService.getNodeServerAddress(),
            {
                secure: true,
                path: '/socket.io'
            });

        socket = socketFactory({
            ioSocket: io_socket
        });

        subscribe(token);

        socket.on('error-jwt', function(error) {
            if(error === 'JWTExpired') {
                JWTService.getJWT().then(function (data) {
                    locker.put('token', data.token);
                    subscribe(data.token);
                    $rootScope.$emit('service-reload');
                    $rootScope.$broadcast('controller-reload');
                });
            }
        });

        function subscribe(subscribe_token) {
            socket.emit('subscription', {
                nodejs_server_version: SharedPropertiesService.getNodeServerVersion(),
                token                : subscribe_token,
                room_id              : 'trafficlights_' + SharedPropertiesService.getCampaignId(),
                uuid                 : SharedPropertiesService.getUUID()
            });
        }

        return socket;
    }
}
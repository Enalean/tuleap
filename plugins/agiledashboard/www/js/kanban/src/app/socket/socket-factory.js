(function () {
    angular
        .module('socket')
        .service('SocketFactory', SocketFactory);

    SocketFactory.$inject = ['socketFactory', 'SharedPropertiesService', 'locker', 'JWTService'];

    function SocketFactory(socketFactory, SharedPropertiesService, locker, JWTService) {
        if (SharedPropertiesService.getNodeServerAddress()) {
            var kanban = SharedPropertiesService.getKanban();
            var kanban_id;
            if(kanban) {
                kanban_id = kanban.id;
            }
            return JWTService.getJWT().then(function(data) {
                locker.driver('session').put('token', data.token);

                var io_socket = io.connect('https://' + SharedPropertiesService.getNodeServerAddress(),
                    {
                        secure: true,
                        path: '/socket.io'
                    });

                socket = socketFactory({
                    ioSocket: io_socket
                });

                subscribe();

                socket.on('error-jwt', function(error) {
                    if(error === 'JWTExpired') {
                        JWTService.getJWT().then(function (data) {
                            locker.driver('session').put('token', data.token);
                            subscribe();
                        });
                    }
                });

                function subscribe() {
                    socket.emit('subscription', {
                        token: locker.driver('session').get('token'),
                        room_id: kanban_id,
                        uuid: SharedPropertiesService.getUUID()
                    });
                }

                return socket;
            });

        }
    }
})();
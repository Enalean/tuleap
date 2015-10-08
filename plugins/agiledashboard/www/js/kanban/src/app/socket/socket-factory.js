(function () {
    angular
        .module('socket')
        .service('SocketFactory', SocketFactory);

    SocketFactory.$inject = ['socketFactory', 'SharedPropertiesService'];

    function SocketFactory(socketFactory, SharedPropertiesService) {
        if (SharedPropertiesService.getNodeServerAddress()) {
            var kanban = SharedPropertiesService.getKanban();
            var kanban_id;
            if(kanban) {
                kanban_id = kanban.id;
            }
            var user_id    = SharedPropertiesService.getUserId();

            var io_socket = io.connect('https://' + SharedPropertiesService.getNodeServerAddress(),
                {
                    secure: true,
                    path: '/socket.io'
                });

            socket = socketFactory({
                ioSocket: io_socket
            });

            socket.emit('subscription', {
                room_id: kanban_id,
                user_id  : user_id,
                uuid     : SharedPropertiesService.getUUID()
            });

            return socket;
        }
    }
})();
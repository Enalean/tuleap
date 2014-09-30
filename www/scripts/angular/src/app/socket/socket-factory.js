(function () {
    angular
        .module('socket')
        .service('SocketFactory', SocketFactory);

    SocketFactory.$inject = ['socketFactory', 'SharedPropertiesService'];

    function SocketFactory(socketFactory, SharedPropertiesService) {
        var io_socket = io.connect('https://' + SharedPropertiesService.getNodeServerAddress(), { secure: true });

        socket = socketFactory({
            ioSocket: io_socket
        });

        var current_user = SharedPropertiesService.getCurrentUser();

        socket.emit('subscription', {
            project_id: SharedPropertiesService.getProjectId(),
            user_id: current_user.id,
            token: current_user.token
        });

        return socket;
    }
})();

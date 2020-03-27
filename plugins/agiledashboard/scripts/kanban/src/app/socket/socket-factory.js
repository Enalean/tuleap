import "angular-socket-io";
import io from "socket.io-client";

export default SocketFactory;

SocketFactory.$inject = ["socketFactory", "SharedPropertiesService"];

function SocketFactory(socketFactory, SharedPropertiesService) {
    if (SharedPropertiesService.getNodeServerAddress()) {
        var io_socket = io.connect("https://" + SharedPropertiesService.getNodeServerAddress(), {
            secure: true,
            path: "/socket.io",
        });

        return socketFactory({
            ioSocket: io_socket,
        });
    }
}

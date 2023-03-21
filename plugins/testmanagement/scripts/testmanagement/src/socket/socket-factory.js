import io from "socket.io-client";

export default SocketFactory;

SocketFactory.$inject = ["socketFactory"];

function SocketFactory(socketFactory) {
    return socketFactory({
        ioSocket: io.connect("/testmanagement", {
            secure: true,
            path: "/local-socket.io",
        }),
    });
}

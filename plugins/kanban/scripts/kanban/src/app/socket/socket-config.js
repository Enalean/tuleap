export default SocketConfig;

SocketConfig.$inject = ["lockerProvider"];

function SocketConfig(lockerProvider) {
    lockerProvider.defaults({
        driver: "session",
        namespace: "socket",
        separator: ".",
        eventsEnabled: false,
        extend: {},
    });
}

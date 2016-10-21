angular
    .module('socket')
    .config(['lockerProvider', function config(lockerProvider) {
        lockerProvider.defaults({
            driver: 'session',
            namespace: 'socket',
            separator: '.',
            eventsEnabled: false,
            extend: {}
        });
    }]);
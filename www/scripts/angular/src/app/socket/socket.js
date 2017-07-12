import angular from 'angular';
import angular_locker from 'angular-locker';

import 'angular-socket-io';

import SocketConfig from './socket-config.js';
import SocketService from './socket-service.js';
import SocketFactory from './socket-factory.js';
import SocketDisconnectDirective from './socket-disconnect-directive.js';

export default angular.module('socket', [
    angular_locker,
    'btford.socket-io'
])
.config(SocketConfig)
.service('SocketService', SocketService)
.service('SocketFactory', SocketFactory)
.directive('socketDisconnect', SocketDisconnectDirective)
.name;


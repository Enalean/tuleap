angular
    .module('socket')
    .directive('socketDisconnect', SocketDisconnectDirective);

SocketDisconnectDirective.$inject = [
    'SocketService'
];

function SocketDisconnectDirective(
    SocketService
) {
    return {
        restrict   : 'E',
        scope      : {},
        templateUrl: 'socket/socket-disconnect.tpl.html',
        link       : function(scope) {
            scope.checkDisconnect = SocketService.checkDisconnect;
        }
    };
}
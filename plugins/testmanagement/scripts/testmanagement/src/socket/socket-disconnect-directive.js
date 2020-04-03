import "./socket-disconnect.tpl.html";

export default SocketDisconnectDirective;

SocketDisconnectDirective.$inject = ["SocketService"];

function SocketDisconnectDirective(SocketService) {
    return {
        restrict: "E",
        scope: {},
        templateUrl: "socket-disconnect.tpl.html",
        link: function (scope) {
            scope.checkDisconnect = SocketService.checkDisconnect;
        },
    };
}

angular
    .module('backlog')
    .directive('backlog', Backlog);

function Backlog() {
    return {
        restrict        : 'A',
        scope           : false,
        templateUrl     : 'backlog/backlog.tpl.html',
        controller      : 'BacklogController as backlog',
        bindToController: true
    };
}

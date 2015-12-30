angular
    .module('backlog')
    .directive('backlog', Backlog);

function Backlog() {
    return {
        restrict        : 'E',
        scope           : false,
        replace         : false,
        templateUrl     : 'backlog/backlog.tpl.html',
        controller      : 'BacklogController as backlog',
        bindToController: true
    };
}

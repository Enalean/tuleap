angular
    .module('backlog-item')
    .directive('backlogItem', BacklogItem);

function BacklogItem() {
    return {
        restrict   : 'E',
        scope      : false,
        replace    : false,
        templateUrl: 'backlog-item/backlog-item.tpl.html',
        controller : 'BacklogItemController as backlogItemController',
        bindToController: true
    };
}

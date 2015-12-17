angular
    .module('backlog-item')
    .directive('backlogItemDetails', BacklogItemDetails);

function BacklogItemDetails() {
    return {
        restrict   : 'E',
        scope      : false,
        replace    : false,
        templateUrl: 'backlog-item/backlog-item-details.tpl.html'
    };
}

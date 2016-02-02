angular
    .module('backlog-item')
    .directive('backlogItemDetails', BacklogItemDetails);

function BacklogItemDetails() {
    return {
        restrict   : 'E',
        scope      : false,
        replace    : false,
        controller : 'BacklogItemDetailsController as details_controller',
        templateUrl: 'backlog-item/backlog-item-details.tpl.html'
    };
}

angular
    .module('backlog-item-details')
    .directive('backlogItemDetails', BacklogItemDetails);

function BacklogItemDetails() {
    return {
        restrict: 'EA',
        scope   : {
            backlog_item     : '=backlogItemDetails',
            moveToTop        : '&',
            moveToBottom     : '&',
            current_milestone: '=currentMilestone'
        },
        controller      : 'BacklogItemDetailsController as details',
        templateUrl     : 'backlog-item/backlog-item-details/backlog-item-details.tpl.html',
        bindToController: true
    };
}

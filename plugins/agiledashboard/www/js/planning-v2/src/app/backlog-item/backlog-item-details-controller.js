angular
    .module('backlog-item')
    .controller('BacklogItemDetailsController', BacklogItemDetailsController);

BacklogItemDetailsController.$inject = [
    '$scope'
];

function BacklogItemDetailsController(
    $scope
) {
    var self = this;

    _.extend(self, {
        backlog_item: $scope.backlog_item,
        moveToTop   : moveToTop,
        moveToBottom: moveToBottom
    });

    /**
     * Crappy method, but we're forced to manually call parent scope method because it's not inherited
     */
    function moveToTop(backlog_item) {
        if ($scope.$parent.backlogItemController) {
            $scope.$parent.backlogItemController.moveToTop(backlog_item);
        } else if ($scope.$parent.milestoneController) {
            $scope.$parent.milestoneController.moveToTop(backlog_item);
        } else if ($scope.$parent.backlog) {
            $scope.$parent.backlog.moveToTop(backlog_item);
        }
    }

    /**
     * Crappy method, but we're forced to manually call parent scope method because it's not inherited
     */
    function moveToBottom(backlog_item) {
        if ($scope.$parent.backlogItemController) {
            $scope.$parent.backlogItemController.moveToBottom(backlog_item);
        } else if ($scope.$parent.milestoneController) {
            $scope.$parent.milestoneController.moveToBottom(backlog_item);
        } else if ($scope.$parent.backlog) {
            $scope.$parent.backlog.moveToBottom(backlog_item);
        }
    }
}

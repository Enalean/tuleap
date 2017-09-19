angular.module('tuleap.pull-request')
    .directive('labels', Labels);

Labels.$inject = [];

function Labels() {
    return {
        restrict: 'E',
        scope   : {
            pullRequestId: '@',
            projectId    : '@'
        },
        controller      : 'LabelsController',
        controllerAs    : 'LabelCtrl',
        bindToController: true
    };
}

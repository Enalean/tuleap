angular.module('tuleap.pull-request')
    .directive('labels', Labels);

Labels.$inject = ['$window'];

function Labels($window) {
    return {
        restrict: 'E',
        link: function (scope, element, attrs) {
            $window.LabelsCreator.create(element[0], attrs.labelsEndpoint);
        }
    };
}

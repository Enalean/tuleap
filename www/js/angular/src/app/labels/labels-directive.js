angular.module('tuleap.pull-request')
    .directive('labels', Labels);

Labels.$inject = ['$window'];

function Labels($window) {
    return {
        restrict: 'E',
        link: function (scope, element, attrs) {
            scope.$watch(function() {
                return attrs.labelsEndpoint;
            }, function(new_value) {
                $window.LabelsCreator.create(element[0], new_value);
            });
        }
    };
}

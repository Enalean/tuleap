angular
    .module('tuleap.pull-request')
    .directive('overview', OverviewDirective);

function OverviewDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'overview/overview.tpl.html',
        controller      : 'OverviewController as overview',
        bindToController: true
    };
}

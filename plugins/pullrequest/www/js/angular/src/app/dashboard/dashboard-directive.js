angular
    .module('tuleap.pull-request')
    .directive('dashboard', DashboardDirective);

function DashboardDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'dashboard/dashboard.tpl.html',
        controller      : 'DashboardController as dashboard_controller',
        bindToController: true
    };
}

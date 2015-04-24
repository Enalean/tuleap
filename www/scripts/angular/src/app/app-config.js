angular
    .module('trafficlights')
    .config(TrafficlightsConfig);

TrafficlightsConfig.$inject = ['$stateProvider', '$urlRouterProvider', '$breadcrumbProvider'];

function TrafficlightsConfig($stateProvider, $urlRouterProvider, $breadcrumbProvider) {
    $urlRouterProvider.otherwise('/campaigns');
    $breadcrumbProvider.setOptions({
        templateUrl: 'breadcrumb.tpl.html'
    });
}
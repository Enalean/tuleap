import './breadcrumb.tpl.html';

export default TrafficlightsConfig;

TrafficlightsConfig.$inject = ['$urlRouterProvider', '$breadcrumbProvider'];

function TrafficlightsConfig($urlRouterProvider, $breadcrumbProvider) {
    $urlRouterProvider.otherwise('/campaigns');
    $breadcrumbProvider.setOptions({
        prefixStateName: 'campaigns.milestone',
        templateUrl: 'breadcrumb.tpl.html'
    });
}

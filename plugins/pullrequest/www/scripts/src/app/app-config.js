export default MainConfig;

MainConfig.$inject = ["$urlRouterProvider", "$locationProvider"];

function MainConfig($urlRouterProvider, $locationProvider) {
    $locationProvider.hashPrefix("");
    $urlRouterProvider.when("/pull-requests/{id:[0-9]+}", "/pull-requests/{id:[0-9]+}/overview");
    $urlRouterProvider.otherwise("/dashboard");
}

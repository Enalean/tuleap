export default MainConfig;

MainConfig.$inject = ["$urlRouterProvider"];

function MainConfig($urlRouterProvider) {
    $urlRouterProvider.when("/pull-requests/{id:[0-9]+}", "/pull-requests/{id:[0-9]+}/overview");
    $urlRouterProvider.otherwise("/dashboard");
}

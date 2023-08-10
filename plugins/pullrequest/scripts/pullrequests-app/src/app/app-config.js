export default MainConfig;

MainConfig.$inject = ["$urlRouterProvider", "$locationProvider"];

function MainConfig($urlRouterProvider, $locationProvider) {
    $locationProvider.hashPrefix("");
    $urlRouterProvider.otherwise("/dashboard");
}

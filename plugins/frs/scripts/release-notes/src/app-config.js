export default FrsConfig;

FrsConfig.$inject = ["$urlRouterProvider"];

function FrsConfig($urlRouterProvider) {
    $urlRouterProvider.otherwise("/");
}

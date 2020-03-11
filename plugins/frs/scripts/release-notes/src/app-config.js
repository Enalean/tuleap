export default FrsConfig;

FrsConfig.$inject = ["$showdownProvider", "$urlRouterProvider"];

function FrsConfig($showdownProvider, $urlRouterProvider) {
    $showdownProvider.setOption("sanitize", true);
    $showdownProvider.setOption("simplifiedAutoLink", true);

    $urlRouterProvider.otherwise("/");
}

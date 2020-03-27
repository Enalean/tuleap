export default DashboardConfig;

DashboardConfig.$inject = ["$stateProvider"];

function DashboardConfig($stateProvider) {
    $stateProvider.state("dashboard", {
        url: "/dashboard",
        template: '<div dashboard id="dashboard"></div>',
    });
}

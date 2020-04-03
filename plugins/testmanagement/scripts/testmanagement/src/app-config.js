import "./breadcrumb.tpl.html";

export default TestManagementConfig;

TestManagementConfig.$inject = ["$urlRouterProvider", "$breadcrumbProvider"];

function TestManagementConfig($urlRouterProvider, $breadcrumbProvider) {
    $urlRouterProvider.otherwise("/campaigns");
    $breadcrumbProvider.setOptions({
        prefixStateName: "campaigns.milestone",
        templateUrl: "breadcrumb.tpl.html",
    });
}

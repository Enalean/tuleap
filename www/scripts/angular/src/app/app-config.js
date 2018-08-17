import "./breadcrumb.tpl.html";

export default TestManagementConfig;

TestManagementConfig.$inject = ["$urlRouterProvider", "$breadcrumbProvider", "$compileProvider"];

function TestManagementConfig($urlRouterProvider, $breadcrumbProvider, $compileProvider) {
    $urlRouterProvider.otherwise("/campaigns");
    $breadcrumbProvider.setOptions({
        prefixStateName: "campaigns.milestone",
        templateUrl: "breadcrumb.tpl.html"
    });

    // To remove this setting, move all init() code
    // of directive controllers to $onInit
    $compileProvider.preAssignBindingsEnabled(true);
}

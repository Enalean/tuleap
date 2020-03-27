import "./execution-list.tpl.html";
import "./execution-detail.tpl.html";

export default ExecutionConfig;

ExecutionConfig.$inject = ["$stateProvider"];

function ExecutionConfig($stateProvider) {
    $stateProvider
        .state("campaigns.executions", {
            url: "/{id:int}",
            controller: "ExecutionListCtrl",
            templateUrl: "execution-list.tpl.html",
            ncyBreadcrumb: {
                label: "{{ campaign.label }}",
                parent: "campaigns.list",
            },
        })
        .state("campaigns.executions.detail", {
            url: "/{execid:int}/{defid:int}",
            controller: "ExecutionDetailCtrl",
            templateUrl: "execution-detail.tpl.html",
            ncyBreadcrumb: {
                label: "{{ execution.definition.summary }}",
                parent: "campaigns.executions",
            },
        });
}

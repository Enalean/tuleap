import "./graph.tpl.html";

export default GraphConfig;

GraphConfig.$inject = ["$stateProvider"];

function GraphConfig($stateProvider) {
    $stateProvider.state("graph", {
        url: "/graph/{id:[0-9]+}",
        controller: "GraphCtrl as graph",
        templateUrl: "graph.tpl.html",
        ncyBreadcrumb: {
            skip: true,
        },
    });
}

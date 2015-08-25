(function () {
    angular
        .module('graph')
        .config(GraphConfig);

    GraphConfig.$inject = ['$stateProvider'];

    function GraphConfig($stateProvider) {
        $stateProvider
            .state('graph', {
                url:         '/graph/{id:[0-9]+}',
                controller:  'GraphCtrl as graph',
                templateUrl: 'graph/graph.tpl.html',
                data: {
                    ncyBreadcrumbSkip: true
                }
            });
    }
})();

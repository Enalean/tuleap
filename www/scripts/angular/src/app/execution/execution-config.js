(function () {
    angular
        .module('execution')
        .config(ExecutionConfig);

    ExecutionConfig.$inject = ['$stateProvider'];

    function ExecutionConfig($stateProvider) {
        $stateProvider
            .state('campaigns.executions', {
                url:         '/{id:[0-9]+}',
                controller:  'ExecutionListCtrl',
                templateUrl: 'execution/execution-list.tpl.html',
                data: {
                    ncyBreadcrumbLabel:  '{{ campaign.label }}',
                    ncyBreadcrumbParent: 'campaigns.list'
                }
            })
            .state('campaigns.executions.detail', {
                url:         '/{execid:[0-9]+}/{defid:[0-9]+}',
                controller:  'ExecutionDetailCtrl',
                templateUrl: 'execution/execution-detail.tpl.html',
                data: {
                    ncyBreadcrumbLabel:  '{{ execution.definition.summary }}',
                    ncyBreadcrumbParent: 'campaigns.executions'
                }
            });
    }
})();
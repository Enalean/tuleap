angular
    .module('execution')
    .config(ExecutionConfig);

ExecutionConfig.$inject = ['$stateProvider'];

function ExecutionConfig($stateProvider) {
    $stateProvider.state('campaigns.executions', {
        url: '/{id:[0-9]+}-{slug}',
        controller: 'ExecutionListCtrl',
        templateUrl: 'execution/execution-list.tpl.html',
        data: {
            ncyBreadcrumbLabel: '{{ campaign.name }}',
            ncyBreadcrumbParent: 'campaigns.list'
        }
    });
}
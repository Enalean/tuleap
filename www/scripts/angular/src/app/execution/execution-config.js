angular
    .module('execution')
    .config(ExecutionConfig);

ExecutionConfig.$inject = ['$stateProvider'];

function ExecutionConfig($stateProvider) {
    $stateProvider.state('campaigns.executions', {
        url: '/:id-:slug',
        controller: 'ExecutionListCtrl',
        templateUrl: 'execution/execution-list.tpl.html',
        data: {
            ncyBreadcrumbLabel: '{{ campaign_label }}',
            ncyBreadcrumbParent: 'campaigns.list'
        }
    });
}
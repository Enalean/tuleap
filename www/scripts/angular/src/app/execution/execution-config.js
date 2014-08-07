angular
    .module('execution')
    .config(ExecutionConfig);

ExecutionConfig.$inject = ['$stateProvider'];

function ExecutionConfig($stateProvider) {
    $stateProvider.state('executions', {
        url: '/campaigns/:id/executions',
        views: {
            "main": {
                controller: 'ExecutionListCtrl',
                templateUrl: 'execution/execution-list.tpl.html'
            }
        }
    });
}
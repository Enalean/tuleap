angular
    .module('execution')
    .config(ExecutionConfig);

ExecutionConfig.$inject = ['$stateProvider'];

function ExecutionConfig($stateProvider) {
    $stateProvider.state('executions', {
        url: '/campaigns/:id-:slug',
        views: {
            "main": {
                controller: 'ExecutionListCtrl',
                templateUrl: 'execution/execution-list.tpl.html'
            }
        }
    });
}
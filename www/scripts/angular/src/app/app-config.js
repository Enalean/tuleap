angular
    .module('testing')
    .config(TestingConfig);

TestingConfig.$inject = ['$stateProvider', '$urlRouterProvider', '$breadcrumbProvider'];

function TestingConfig($stateProvider, $urlRouterProvider, $breadcrumbProvider) {
    $urlRouterProvider.otherwise('/campaigns');
    $breadcrumbProvider.setOptions({
        templateUrl: 'breadcrumb.tpl.html'
    });
}
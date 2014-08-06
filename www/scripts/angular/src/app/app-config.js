angular
    .module('testing')
    .config(TestingConfig);

TestingConfig.$inject = ['$stateProvider', '$urlRouterProvider'];

function TestingConfig($stateProvider, $urlRouterProvider) {
    $urlRouterProvider.otherwise('/campaigns');
}
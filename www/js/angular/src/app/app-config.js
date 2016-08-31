angular
    .module('tuleap.pull-request')
    .config(MainConfig);

MainConfig.$inject = [
    '$urlRouterProvider'
];

function MainConfig(
    $urlRouterProvider
) {
    $urlRouterProvider.otherwise('/dashboard');
}

(function () {
    angular
        .module('planning')
        .config(PlanningConfig);

    PlanningConfig.$inject = ['$stateProvider', '$urlRouterProvider'];

    function PlanningConfig($stateProvider, $urlRouterProvider) {
        $urlRouterProvider.otherwise('/planning');

        $stateProvider.state('planning', {
            url: "/planning",
            controller: 'PlanningCtrl',
            templateUrl: "planning.tpl.html"
        });
    }
})();

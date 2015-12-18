(function () {
    angular
        .module('planning')
        .config(PlanningConfig);

    PlanningConfig.$inject = ['$stateProvider', '$urlRouterProvider', '$animateProvider'];

    function PlanningConfig($stateProvider, $urlRouterProvider, $animateProvider) {
        $urlRouterProvider.otherwise('/planning');

        $animateProvider.classNameFilter(/do-animate/);

        $stateProvider.state('planning', {
            url: "/planning",
            controller: 'PlanningCtrl',
            controllerAs: 'planning',
            templateUrl: "planning.tpl.html"
        });
    }
})();

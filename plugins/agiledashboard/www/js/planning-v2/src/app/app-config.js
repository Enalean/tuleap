(function () {
    angular
        .module('planning')
        .config(PlanningConfig);

    PlanningConfig.$inject = ['$animateProvider'];

    function PlanningConfig($animateProvider) {
        $animateProvider.classNameFilter(/do-animate/);
    }
})();

import './planning.tpl.html';
import PlanningController from './app-planning-controller.js';

export default PlanningConfig;

PlanningConfig.$inject = [
    '$stateProvider',
    '$urlRouterProvider',
    '$animateProvider'
];

function PlanningConfig(
    $stateProvider,
    $urlRouterProvider,
    $animateProvider
) {
    $urlRouterProvider.otherwise('/planning');

    $animateProvider.classNameFilter(/do-animate/);

    $stateProvider.state('planning', {
        url         : "/planning",
        controller  : PlanningController,
        controllerAs: 'planning',
        templateUrl : "planning.tpl.html"
    });
}

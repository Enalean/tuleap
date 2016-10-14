import './kanban.tpl.html';
import KanbanCtrl from './app-kanban-controller.js';

export default KanbanConfig;

KanbanConfig.$inject = [
    '$stateProvider',
    '$urlRouterProvider',
    'RestangularProvider',
    '$animateProvider'
];

function KanbanConfig(
    $stateProvider,
    $urlRouterProvider,
    RestangularProvider,
    $animateProvider
) {
    $urlRouterProvider.otherwise('/kanban');

    $animateProvider.classNameFilter(/do-animate/);

    $stateProvider.state('kanban', {
        url         : "/kanban",
        controller  : KanbanCtrl,
        controllerAs: 'kanban',
        templateUrl : "kanban.tpl.html"
    });

    RestangularProvider.setFullResponse(true);
    RestangularProvider.setBaseUrl('/api/v1');
    RestangularProvider.setDefaultHeaders({
        'Content-Type': 'application/json'
    });
}

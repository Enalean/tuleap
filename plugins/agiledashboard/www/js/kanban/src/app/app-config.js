import './kanban.tpl.html';
import KanbanCtrl from './app-kanban-controller.js';

export default KanbanConfig;

KanbanConfig.$inject = [
    '$stateProvider',
    '$urlRouterProvider',
    'RestangularProvider'
];

function KanbanConfig(
    $stateProvider,
    $urlRouterProvider,
    RestangularProvider
) {
    $urlRouterProvider.otherwise('/kanban');

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

angular
    .module('kanban-column')
    .directive('kanbanColumn', KanbanColumn);

function KanbanColumn() {
    return {
        restrict        : 'AE',
        scope           : true,
        templateUrl     : 'kanban-column/kanban-column.tpl.html',
        controller      : 'KanbanColumnController',
        controllerAs    : '$ctrl',
        bindToController: true
    };
}

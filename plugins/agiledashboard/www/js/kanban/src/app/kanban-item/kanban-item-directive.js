angular
    .module('kanban-item')
    .directive('kanbanItem', KanbanItem);

function KanbanItem() {
    return {
        restrict        : 'AE',
        scope           : true,
        controller      : 'KanbanItemController',
        controllerAs    : '$ctrl',
        bindToController: true,
        templateUrl     : 'kanban-item/kanban-item.tpl.html'
    };
}

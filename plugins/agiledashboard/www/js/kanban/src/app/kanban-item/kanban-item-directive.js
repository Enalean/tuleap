angular
    .module('kanban-item')
    .directive('kanbanItem', KanbanItem);

function KanbanItem() {
    return {
        restrict   : 'AE',
        templateUrl: 'kanban-item/kanban-item.tpl.html'
    };
}

import "./kanban-column.tpl.html";

export default KanbanColumn;

function KanbanColumn() {
    return {
        restrict: "AE",
        scope: true,
        templateUrl: "kanban-column.tpl.html",
        controller: "KanbanColumnController",
        controllerAs: "$ctrl",
        bindToController: true,
    };
}

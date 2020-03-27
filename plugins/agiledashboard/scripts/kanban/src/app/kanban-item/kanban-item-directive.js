import "./kanban-item.tpl.html";
import KanbanItemController from "./kanban-item-controller.js";

export default KanbanItem;

function KanbanItem() {
    return {
        restrict: "AE",
        scope: true,
        controller: KanbanItemController,
        controllerAs: "$ctrl",
        bindToController: true,
        templateUrl: "kanban-item.tpl.html",
    };
}

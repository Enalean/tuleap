import "./go-to-kanban.tpl.html";
import GoToKanbanCtrl from "./go-to-kanban-controller.js";

export default GoToKanban;

function GoToKanban() {
    return {
        restrict: "E",
        controller: GoToKanbanCtrl,
        controllerAs: "go_to_kanban",
        templateUrl: "go-to-kanban.tpl.html",
        scope: {},
    };
}

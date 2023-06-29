import "./kanban.tpl.html";
import KanbanCtrl from "./app-kanban-controller.js";

export default () => {
    return {
        restrict: "E",
        controller: KanbanCtrl,
        controllerAs: "kanban",
        templateUrl: "kanban.tpl.html",
        scope: {},
    };
};

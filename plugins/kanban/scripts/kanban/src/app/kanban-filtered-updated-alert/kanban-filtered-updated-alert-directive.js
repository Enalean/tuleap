import "./kanban-filtered-updated-alert.tpl.html";
import KanbanFilteredUpdatedAlertCtrl from "./kanban-filtered-updated-alert-controller.js";

export default KanbanFilteredUpdatedAlert;

function KanbanFilteredUpdatedAlert() {
    return {
        restrict: "E",
        controller: KanbanFilteredUpdatedAlertCtrl,
        controllerAs: "kanban_filtered_updated_alert",
        templateUrl: "kanban-filtered-updated-alert.tpl.html",
        scope: {},
    };
}

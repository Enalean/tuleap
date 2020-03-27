import { getAccessibilityMode } from "../user-accessibility-mode.js";

export default KanbanItemController;

KanbanItemController.$inject = ["KanbanFilterValue"];

function KanbanItemController(KanbanFilterValue) {
    const self = this;
    Object.assign(self, {
        user_has_accessibility_mode: getAccessibilityMode(),
        kanban_filter: KanbanFilterValue,
    });
}

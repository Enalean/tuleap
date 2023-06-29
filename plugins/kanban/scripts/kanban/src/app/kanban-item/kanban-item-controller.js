import { getAccessibilityMode } from "../user-accessibility-mode.js";

export default KanbanItemController;

KanbanItemController.$inject = ["KanbanFilterValue"];

function KanbanItemController(KanbanFilterValue) {
    const self = this;
    Object.assign(self, {
        user_has_accessibility_mode: getAccessibilityMode(),
        kanban_filter: KanbanFilterValue,
        slugifyLabel,
    });

    // For testing purpose
    function slugifyLabel(label) {
        return label.replace(/\s/g, "_").toLowerCase();
    }
}

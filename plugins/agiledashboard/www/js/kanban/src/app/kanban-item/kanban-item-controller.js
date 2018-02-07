export default KanbanItemController;

KanbanItemController.$inject = [
    'KanbanFilterValue'
];

function KanbanItemController(
    KanbanFilterValue
) {
    const self = this;
    Object.assign(self, {
        kanban_filter: KanbanFilterValue
    });
}

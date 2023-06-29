export default WipHeaderCtrl;

WipHeaderCtrl.$inject = [
    "KanbanService",
    "FilterTrackerReportService",
    "SharedPropertiesService",
    "RestErrorService",
];

function WipHeaderCtrl(
    KanbanService,
    FilterTrackerReportService,
    SharedPropertiesService,
    RestErrorService
) {
    const self = this;
    Object.assign(self, {
        isWipBadgeShown,
        isColumnLoaded,
        setWipLimit,
        isUserAdmin: SharedPropertiesService.getUserIsAdmin,
    });

    function isWipBadgeShown() {
        return (
            isNotArchiveBacklog() && !FilterTrackerReportService.isFiltersTrackerReportSelected()
        );
    }

    function isColumnLoaded() {
        return self.column.loading_items || self.column.fully_loaded;
    }

    function isNotArchiveBacklog() {
        return self.column.id !== "archive" && self.column.id !== "backlog";
    }

    function setWipLimit() {
        const kanban_id = SharedPropertiesService.getKanban().id;

        self.column.saving_wip = true;
        return KanbanService.editColumn(kanban_id, self.column).then(
            () => {
                self.column.limit = self.column.limit_input;
                self.column.wip_in_edit = false;
                self.column.saving_wip = false;
            },
            (response) => {
                RestErrorService.reload(response);
            }
        );
    }
}

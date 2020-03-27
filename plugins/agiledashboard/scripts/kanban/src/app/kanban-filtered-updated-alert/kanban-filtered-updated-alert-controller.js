export default KanbanFilteredUpdatedAlertController;

KanbanFilteredUpdatedAlertController.$inject = ["$window", "KanbanFilteredUpdatedAlertService"];

function KanbanFilteredUpdatedAlertController($window, KanbanFilteredUpdatedAlertService) {
    const self = this;

    Object.assign(self, {
        isDisplayed,
        reload,
    });

    function isDisplayed() {
        return KanbanFilteredUpdatedAlertService.isCardUpdated();
    }

    function reload() {
        $window.location.reload();
    }
}

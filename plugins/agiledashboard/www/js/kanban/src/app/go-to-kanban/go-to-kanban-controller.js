export default GoToKanbanController;

GoToKanbanController.$inject = ["SharedPropertiesService"];

function GoToKanbanController(SharedPropertiesService) {
    var self = this;

    self.kanban_url = SharedPropertiesService.getKanbanUrl();

    self.showLinkToKanban = showLinkToKanban;

    function showLinkToKanban() {
        return userIsOnWidget();
    }

    function userIsOnWidget() {
        return SharedPropertiesService.getUserIsOnWidget();
    }
}

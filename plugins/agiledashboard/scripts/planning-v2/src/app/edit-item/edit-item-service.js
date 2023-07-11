export default EditItemService;

EditItemService.$inject = [
    "BacklogItemCollectionService",
    "NewTuleapArtifactModalService",
    "MilestoneService",
    "SharedPropertiesService",
    "BacklogService",
];

function EditItemService(
    BacklogItemCollectionService,
    NewTuleapArtifactModalService,
    MilestoneService,
    SharedPropertiesService,
    BacklogService
) {
    var self = this;
    self.showEditModal = showEditModal;
    self.removeElementFromExplicitBacklog = removeElementFromExplicitBacklog;

    function showEditModal($event, backlog_item, milestone) {
        var when_left_mouse_click = 1;

        function callback(item_id, changes) {
            return BacklogItemCollectionService.refreshBacklogItem(item_id, changes).then(
                function () {
                    if (milestone) {
                        MilestoneService.updateInitialEffort(milestone);
                    }
                }
            );
        }

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();

            NewTuleapArtifactModalService.showEdition(
                SharedPropertiesService.getUserId(),
                backlog_item.artifact.tracker.id,
                backlog_item.artifact.id,
                callback
            );
        }
    }

    function removeElementFromExplicitBacklog($event, backlog_item) {
        var when_left_mouse_click = 1;

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();
            BacklogItemCollectionService.removeExplicitBacklogElement(backlog_item);
            BacklogService.removeBacklogItemsFromBacklog([backlog_item]);
        }
    }
}

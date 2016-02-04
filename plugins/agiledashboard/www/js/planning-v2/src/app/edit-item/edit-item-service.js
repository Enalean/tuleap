angular
    .module('edit-item')
    .service('EditItemService', EditItemService);

EditItemService.$inject = [
    'BacklogItemCollectionService',
    'NewTuleapArtifactModalService',
    'MilestoneService',
    'SharedPropertiesService'
];

function EditItemService(
    BacklogItemCollectionService,
    NewTuleapArtifactModalService,
    MilestoneService,
    SharedPropertiesService
) {
    var self = this;
    _.extend(self, {
        showEditModal: showEditModal
    });

    function showEditModal($event, backlog_item, milestone) {
        var when_left_mouse_click = 1;

        var callback = function(item_id) {
            return BacklogItemCollectionService.refreshBacklogItem(item_id).then(function() {
                if (milestone) {
                    MilestoneService.updateInitialEffort(milestone);
                }
            });
        };

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
}

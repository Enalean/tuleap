export default MilestoneCollectionService;

MilestoneCollectionService.$inject = ["MilestoneService", "BacklogItemCollectionService"];

function MilestoneCollectionService(MilestoneService, BacklogItemCollectionService) {
    var self = this;
    Object.assign(self, {
        milestones: {
            content: [],
            loading: false,
            open_milestones_fully_loaded: false,
            closed_milestones_fully_loaded: false,
            open_milestones_pagination: { limit: 50, offset: 0 },
            closed_milestones_pagination: { limit: 50, offset: 0 },
        },
        getMilestone: getMilestone,
        refreshMilestone: refreshMilestone,
        removeBacklogItemsFromMilestoneContent: removeBacklogItemsFromMilestoneContent,
        addOrReorderBacklogItemsInMilestoneContent: addOrReorderBacklogItemsInMilestoneContent,
    });

    function getMilestone(milestone_id) {
        return self.milestones.content.find(({ id }) => id === milestone_id);
    }

    function refreshMilestone(milestone_id) {
        var milestone = getMilestone(milestone_id);

        MilestoneService.updateInitialEffort(milestone);
    }

    function removeBacklogItemsFromMilestoneContent(milestone_id, backlog_items) {
        var milestone = getMilestone(milestone_id);

        BacklogItemCollectionService.removeBacklogItemsFromCollection(
            milestone.content,
            backlog_items
        );
    }

    function addOrReorderBacklogItemsInMilestoneContent(milestone_id, backlog_items, compared_to) {
        var milestone = getMilestone(milestone_id);

        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            milestone.content,
            backlog_items,
            compared_to
        );
    }
}

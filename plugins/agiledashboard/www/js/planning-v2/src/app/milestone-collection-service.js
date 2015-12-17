angular
    .module('planning')
    .service('MilestoneCollectionService', MilestoneCollectionService);

MilestoneCollectionService.$inject = [
    'MilestoneService'
];

function MilestoneCollectionService(
    MilestoneService
) {
    var self = this;
    _.extend(self, {
        milestones: {
            content                       : [],
            loading                       : false,
            open_milestones_fully_loaded  : false,
            closed_milestones_fully_loaded: false
        },
        refreshMilestone: refreshMilestone
    });

    function refreshMilestone(milestone_id) {
        var milestone = _.find(self.milestones.content, function(milestone) {
            return milestone.id === milestone_id;
        });

        MilestoneService.updateInitialEffort(milestone);
    }
}

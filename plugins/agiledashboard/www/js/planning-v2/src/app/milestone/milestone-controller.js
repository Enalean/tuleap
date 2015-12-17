angular
    .module('milestone')
    .controller('MilestoneController', MilestoneController);


MilestoneController.$inject = [

];

function MilestoneController(

) {
    var self = this;
    _.extend(self, {
        toggleMilestone: toggleMilestone
    });

    function toggleMilestone($event, milestone) {
        if (! milestone.alreadyLoaded && milestone.content.length === 0) {
            milestone.getContent();
        }

        var target                = $event.target;
        var is_a_create_item_link = false;

        if (target.classList) {
            is_a_create_item_link = target.classList.contains('create-item-link');
        } else {
            is_a_create_item_link = target.parentNode.getElementsByClassName("create-item-link")[0] !== undefined;
        }

        if (! is_a_create_item_link) {
            return milestone.collapsed = ! milestone.collapsed;
        }
    }
}

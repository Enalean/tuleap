angular
    .module('milestone')
    .directive('milestone', Milestone);

function Milestone() {
    return {
        restrict        : 'E',
        scope           : false,
        replace         : false,
        templateUrl     : 'milestone/milestone.tpl.html',
        controller      : 'MilestoneController as milestoneController',
        bindToController: true
    };
}

angular
    .module('tuleap.pull-request')
    .directive('timeline', TimelineDirective);

function TimelineDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'overview/timeline/timeline.tpl.html',
        controller      : 'TimelineController as timeline_controller',
        bindToController: true
    };
}

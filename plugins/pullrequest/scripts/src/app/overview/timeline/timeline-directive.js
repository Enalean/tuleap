import "./timeline.tpl.html";

import TimelineController from "./timeline-controller.js";

export default TimelineDirective;

function TimelineDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "timeline.tpl.html",
        controller: TimelineController,
        controllerAs: "timeline_controller",
        bindToController: true,
    };
}

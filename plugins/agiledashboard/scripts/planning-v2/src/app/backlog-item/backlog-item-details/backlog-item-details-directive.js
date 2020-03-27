import "./backlog-item-details.tpl.html";
import BacklogItemDetailsController from "./backlog-item-details-controller.js";

export default function BacklogItemDetails() {
    return {
        restrict: "EA",
        scope: {
            backlog_item: "=backlogItemDetails",
            moveToTop: "&",
            moveToBottom: "&",
            current_milestone: "=currentMilestone",
            children_context: "@childrenContext",
        },
        controller: BacklogItemDetailsController,
        controllerAs: "details",
        bindToController: true,
        templateUrl: "backlog-item-details.tpl.html",
    };
}

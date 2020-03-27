import "./milestone.tpl.html";
import MilestoneController from "./milestone-controller.js";

export default function Milestone() {
    return {
        restrict: "AE",
        scope: false,
        replace: false,
        controller: MilestoneController,
        controllerAs: "milestoneController",
        bindToController: true,
        templateUrl: "milestone.tpl.html",
    };
}

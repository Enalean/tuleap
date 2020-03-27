import "./backlog-item-selected-bar.tpl.html";
import BacklogItemSelectedBarController from "./backlog-item-selected-bar-controller.js";

export default function BacklogItemSelectedBar() {
    return {
        restrict: "A",
        scope: false,
        controller: BacklogItemSelectedBarController,
        controllerAs: "selected_bar_controller",
        bindToController: true,
        templateUrl: "backlog-item-selected-bar.tpl.html",
    };
}

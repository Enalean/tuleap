import "./backlog.tpl.html";
import BacklogController from "./backlog-controller.js";

export default function Backlog() {
    return {
        restrict: "A",
        scope: false,
        controller: BacklogController,
        controllerAs: "backlog",
        bindToController: true,
        templateUrl: "backlog.tpl.html",
    };
}

import "./header.tpl.html";

import PullRequestHeaderController from "./header-controller.js";

export default PullRequestHeaderDirective;

function PullRequestHeaderDirective() {
    return {
        restrict: "E",
        templateUrl: "header.tpl.html",
        controller: PullRequestHeaderController,
        controllerAs: "pull_request_controller",
        bindToController: true,
    };
}

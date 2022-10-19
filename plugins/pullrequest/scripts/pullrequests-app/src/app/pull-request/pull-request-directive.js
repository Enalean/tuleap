import "./pull-request.tpl.html";

import PullRequestController from "./pull-request-controller.js";

export default PullRequestDirective;

function PullRequestDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "pull-request.tpl.html",
        controller: PullRequestController,
        controllerAs: "pull_request",
        bindToController: true,
    };
}

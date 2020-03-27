import "./pull-request-refs.tpl.html";

import PullRequestRefsController from "./pull-request-refs-controller.js";

export default PullRequestRefsDirective;

function PullRequestRefsDirective() {
    return {
        restrict: "AE",
        scope: {
            pull_request: "=pullRequestData",
        },
        templateUrl: "pull-request-refs.tpl.html",
        controller: PullRequestRefsController,
        controllerAs: "refs_controller",
        bindToController: true,
    };
}

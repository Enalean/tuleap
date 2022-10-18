import "./pull-request-summary.tpl.html";

import PullRequestSummaryController from "./pull-request-summary-controller.js";

export default PullRequestSummaryDirective;

function PullRequestSummaryDirective() {
    return {
        restrict: "AE",
        scope: {
            pull_request: "=pullRequestData",
        },
        templateUrl: "pull-request-summary.tpl.html",
        controller: PullRequestSummaryController,
        controllerAs: "summary_controller",
        bindToController: true,
    };
}

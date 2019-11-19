import "./reviewers.tpl.html";

import ReviewersController from "./reviewers-controller.js";

export default ReviewersDirective;

function ReviewersDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "reviewers.tpl.html",
        controller: ReviewersController,
        controllerAs: "reviewers_controller",
        bindToController: true
    };
}

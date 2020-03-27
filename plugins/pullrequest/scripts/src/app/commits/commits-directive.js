import "./commits.tpl.html";

import CommitsController from "./commits-controller.js";

export default CommitsDirective;

function CommitsDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "commits.tpl.html",
        controller: CommitsController,
        controllerAs: "commits",
        bindToController: true,
    };
}

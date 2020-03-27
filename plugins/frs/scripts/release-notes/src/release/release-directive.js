import "./release.tpl.html";

import ReleaseController from "./release-controller.js";

export default releaseDirective;

function releaseDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "release.tpl.html",
        controller: ReleaseController,
        controllerAs: "$ctrl",
        bindToController: true,
    };
}

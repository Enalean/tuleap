import "./overview.tpl.html";

import OverviewController from "./overview-controller.js";

export default OverviewDirective;

function OverviewDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "overview.tpl.html",
        controller: OverviewController,
        controllerAs: "overview",
        bindToController: true,
    };
}

import "./dashboard.tpl.html";

import DashboardController from "./dashboard-controller.js";

export default DashboardDirective;

function DashboardDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "dashboard.tpl.html",
        controller: DashboardController,
        controllerAs: "dashboard_controller",
        bindToController: true,
    };
}

import "./planning.tpl.html";
import PlanningCtrl from "./planning-controller.js";

export default () => {
    return {
        restrict: "E",
        controller: PlanningCtrl,
        controllerAs: "planning",
        templateUrl: "planning.tpl.html",
        scope: {},
    };
};

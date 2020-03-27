import "./linked-artifacts.tpl.html";

import LinkedArtifactsController from "./linked-artifacts-controller.js";

export default linkedArtifactsDirective;

function linkedArtifactsDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "linked-artifacts.tpl.html",
        controller: LinkedArtifactsController,
        controllerAs: "$ctrl",
        bindToController: true,
    };
}

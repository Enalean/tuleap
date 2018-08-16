import LabelsController from "./labels-controller.js";

export default Labels;

Labels.$inject = [];

function Labels() {
    return {
        restrict: "E",
        scope: {
            pullRequestId: "@",
            projectId: "@"
        },
        controller: LabelsController,
        controllerAs: "LabelCtrl",
        bindToController: true
    };
}

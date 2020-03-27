import LabelsController from "./labels-controller.js";

export default function LabelsBox() {
    return {
        restrict: "E",
        scope: {
            pullRequestId: "@",
            projectId: "@",
            placeholder: "@",
        },
        controller: LabelsController,
        controllerAs: "LabelCtrl",
        bindToController: true,
        template: `<select class="tlp-select labels-box-select-hidden" multiple></select>`,
    };
}

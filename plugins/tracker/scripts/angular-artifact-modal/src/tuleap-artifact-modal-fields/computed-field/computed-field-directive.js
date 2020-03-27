import "./computed-field.tpl.html";
import ComputedFieldController from "./computed-field-controller.js";

export default function ComputedFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalComputedField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: ComputedFieldController,
        controllerAs: "computed_field",
        bindToController: true,
        templateUrl: "computed-field.tpl.html",
    };
}

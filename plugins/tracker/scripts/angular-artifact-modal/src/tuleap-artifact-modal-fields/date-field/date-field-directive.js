import "./date-field.tpl.html";
import DateFieldController from "./date-field-controller.js";

export default function DateFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalDateField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: DateFieldController,
        controllerAs: "date_field",
        bindToController: true,
        templateUrl: "date-field.tpl.html",
    };
}

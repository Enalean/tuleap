import tpl from "./permission-field.tpl.html";
import PermissionFieldController from "./permission-field-controller.js";

export default function PermissionFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalPermissionField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: PermissionFieldController,
        controllerAs: "permission_field",
        bindToController: true,
        template: tpl,
    };
}

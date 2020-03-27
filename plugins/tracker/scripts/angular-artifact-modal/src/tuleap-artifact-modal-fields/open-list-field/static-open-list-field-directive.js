import "./static-open-list-field.tpl.html";
import StaticOpenListFieldController from "./static-open-list-field-controller.js";

export default function StaticOpenListFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalStaticOpenListField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: StaticOpenListFieldController,
        controllerAs: "static_open_list_field",
        bindToController: true,
        templateUrl: "static-open-list-field.tpl.html",
    };
}

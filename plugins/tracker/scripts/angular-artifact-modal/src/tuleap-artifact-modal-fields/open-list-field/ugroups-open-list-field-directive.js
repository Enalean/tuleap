import "./ugroups-open-list-field.tpl.html";
import UgroupsOpenListFieldController from "./ugroups-open-list-field-controller.js";

export default function UgroupsOpenListFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalUgroupsOpenListField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: UgroupsOpenListFieldController,
        controllerAs: "ugroups_open_list_field",
        bindToController: true,
        templateUrl: "ugroups-open-list-field.tpl.html",
    };
}

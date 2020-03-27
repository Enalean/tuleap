import "./link-field.tpl.html";
import LinkFieldController from "./link-field-controller.js";

export default function linkFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalLinkField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
            artifact_id: "=artifactId",
            tracker: "=tracker",
            parent_artifact_id: "=parentArtifactId",
            parent_artifact: "=parentArtifact",
        },
        controller: LinkFieldController,
        controllerAs: "link_field",
        bindToController: true,
        templateUrl: "link-field.tpl.html",
    };
}

import "./file-field.tpl.html";
import FileFieldController from "./file-field-controller.js";

export default FileFieldDirective;

FileFieldDirective.$inject = [];

function FileFieldDirective() {
    return {
        restrict: "EA",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalFileField",
            isDisabled: "&isDisabled",
            value_model: "=valueModel",
        },
        controller: FileFieldController,
        controllerAs: "file_field",
        bindToController: true,
        templateUrl: "file-field.tpl.html",
    };
}

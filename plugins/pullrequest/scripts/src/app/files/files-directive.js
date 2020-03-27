import "./files.tpl.html";

import FilesController from "./files-controller.js";

export default FilesDirective;

function FilesDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "files.tpl.html",
        controller: FilesController,
        controllerAs: "files_controller",
        bindToController: true,
    };
}

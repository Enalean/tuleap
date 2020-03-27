import "./files.tpl.html";

import FilesController from "./files-controller.js";

export default filesDirective;

function filesDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "files.tpl.html",
        controller: FilesController,
        controllerAs: "$ctrl",
        bindToController: true,
    };
}

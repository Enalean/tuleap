import "./file-diff.tpl.html";

import FileDiffController from "./file-diff-controller.js";

export default function FileDiffDirective() {
    return {
        restrict: "A",
        scope: {},
        templateUrl: "file-diff.tpl.html",
        controller: FileDiffController,
        controllerAs: "diff",
        bindToController: true
    };
}

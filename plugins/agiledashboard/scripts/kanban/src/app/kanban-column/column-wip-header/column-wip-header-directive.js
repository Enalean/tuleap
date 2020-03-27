import "./column-wip-header.tpl.html";
import Controller from "./column-wip-header-controller.js";

export default () => {
    return {
        restrict: "E",
        controller: Controller,
        controllerAs: "wip_header",
        bindToController: true,
        templateUrl: "column-wip-header.tpl.html",
        scope: {
            column: "=",
            isColumnWipReached: "&",
        },
    };
};

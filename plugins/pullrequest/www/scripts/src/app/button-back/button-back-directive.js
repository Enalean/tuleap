import "./button-back.tpl.html";

import ButtonBackController from "./button-back-controller.js";

export default ButtonBackDirective;

function ButtonBackDirective() {
    return {
        restrict: "E",
        scope: {},
        templateUrl: "button-back.tpl.html",
        controller: ButtonBackController,
        controllerAs: "button_back_controller",
        bindToController: true
    };
}

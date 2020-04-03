import "./step.tpl.html";

import controller from "./step-controller.js";

export default {
    templateUrl: "step.tpl.html",
    controller,
    bindings: {
        step: "<",
        step_result: "<stepResult",
        execution: "<",
    },
};

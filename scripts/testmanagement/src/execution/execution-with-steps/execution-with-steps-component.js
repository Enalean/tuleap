import "./execution-with-steps.tpl.html";

import controller from "./execution-with-steps-controller.js";

export default {
    templateUrl: "execution-with-steps.tpl.html",
    controller,
    bindings: {
        execution: "<",
    },
};

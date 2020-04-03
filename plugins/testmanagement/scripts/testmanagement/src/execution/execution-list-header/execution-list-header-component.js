import "./execution-list-header.tpl.html";

import controller from "./execution-list-header-controller.js";

export default {
    templateUrl: "execution-list-header.tpl.html",
    controller,
    bindings: {
        handleRemovedExecutionsCallback: "&",
    },
};

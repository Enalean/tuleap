import angular from "angular";

import focusOnClickDirective from "./focus-on-click-directive.js";

angular.module("tuleap-focus", []).directive("tuleapFocusOnClick", focusOnClickDirective);

export default "tuleap-focus";

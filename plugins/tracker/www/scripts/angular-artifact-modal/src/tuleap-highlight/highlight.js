import angular from "angular";

import HighlightDirective from "./highlight-directive.js";

angular.module("tuleap-highlight", []).directive("tuleapHighlightDirective", HighlightDirective);

export default "tuleap-highlight";

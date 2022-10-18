import angular from "angular";

import ResizeDirective from "./resize-directive.js";

export default angular.module("tuleap.resize", []).directive("resize", ResizeDirective).name;

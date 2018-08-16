import angular from "angular";

import DateFieldDirective from "./date-field-directive.js";

export default angular
    .module("tuleap-artifact-modal-date-field", [])
    .directive("tuleapArtifactModalDateField", DateFieldDirective).name;

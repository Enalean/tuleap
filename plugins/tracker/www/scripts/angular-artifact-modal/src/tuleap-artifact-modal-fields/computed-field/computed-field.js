import angular from "angular";
import "angular-gettext";

import focus from "../../tuleap-focus/focus.js";

import ComputedFieldDirective from "./computed-field-directive.js";

angular
    .module("tuleap-artifact-modal-computed-field", ["gettext", focus])
    .directive("tuleapArtifactModalComputedField", ComputedFieldDirective);

export default "tuleap-artifact-modal-computed-field";

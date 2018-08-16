import angular from "angular";
import "angular-gettext";

import focus from "../../tuleap-focus/focus.js";

import ComputedFieldDirective from "./computed-field-directive.js";
import ComputedFieldValidateService from "./computed-field-validate-service.js";

angular
    .module("tuleap-artifact-modal-computed-field", ["gettext", focus])
    .directive("tuleapArtifactModalComputedField", ComputedFieldDirective)
    .service("TuleapArtifactModalComputedFieldValidateService", ComputedFieldValidateService);

export default "tuleap-artifact-modal-computed-field";

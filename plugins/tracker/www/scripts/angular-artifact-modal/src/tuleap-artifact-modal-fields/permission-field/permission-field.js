import angular from "angular";
import "angular-gettext";

import PermissionFieldDirective from "./permission-field-directive.js";
import PermissionFieldValidateService from "./permission-field-validate-service.js";

angular
    .module("tuleap-artifact-modal-permission-field", ["gettext"])
    .directive("tuleapArtifactModalPermissionField", PermissionFieldDirective)
    .service("TuleapArtifactModalPermissionFieldValidateService", PermissionFieldValidateService);

export default "tuleap-artifact-modal-permission-field";

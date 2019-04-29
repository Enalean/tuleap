import angular from "angular";
import "angular-gettext";

import PermissionFieldDirective from "./permission-field-directive.js";

angular
    .module("tuleap-artifact-modal-permission-field", ["gettext"])
    .directive("tuleapArtifactModalPermissionField", PermissionFieldDirective);

export default "tuleap-artifact-modal-permission-field";

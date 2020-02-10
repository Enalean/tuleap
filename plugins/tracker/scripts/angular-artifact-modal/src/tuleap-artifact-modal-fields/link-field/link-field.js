import angular from "angular";
import "angular-gettext";

import LinkFieldDirective from "./link-field-directive.js";

export default angular
    .module("tuleap-artifact-modal-link-field", ["gettext"])
    .directive("tuleapArtifactModalLinkField", LinkFieldDirective).name;

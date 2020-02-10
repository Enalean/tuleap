import angular from "angular";
import filter from "angular-filter";

import "angular-gettext";

import FileFieldDirective from "./file-field-directive.js";
import FileInputDirective from "./file-input-directive.js";

export default angular
    .module("tuleap-artifact-modal-file-field", [filter, "gettext"])
    .directive("tuleapArtifactModalFileField", FileFieldDirective)
    .directive("tuleapArtifactModalFileInput", FileInputDirective).name;

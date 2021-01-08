import angular from "angular";

import TlpModalService from "./tlp-modal-service.js";
import TlpSelect2Directive from "./tlp-select2-directive.js";

export default angular
    .module("angular-tlp", [])
    .service("TlpModalService", TlpModalService)
    .directive("tlpSelect2", TlpSelect2Directive).name;

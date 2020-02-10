import angular from "angular";
import filter from "angular-filter";

import QuotaDisplayDirective from "./quota-display-directive.js";

export default angular
    .module("tuleap-artifact-modal-quota-display", [filter])
    .directive("tuleapArtifactModalQuotaDisplay", QuotaDisplayDirective).name;

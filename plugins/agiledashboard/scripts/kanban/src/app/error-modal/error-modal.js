import angular from "angular";
import angular_tlp from "@tuleap/angular-tlp";

import RestErrorService from "./rest-error-service.js";

angular.module("rest-error", [angular_tlp]).service("RestErrorService", RestErrorService);

export default "rest-error";

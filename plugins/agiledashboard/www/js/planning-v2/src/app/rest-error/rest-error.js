import angular from "angular";

import RestErrorService from "./rest-error-service.js";

export default angular.module("rest-error", []).service("RestErrorService", RestErrorService).name;

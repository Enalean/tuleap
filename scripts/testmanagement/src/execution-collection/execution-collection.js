import angular from "angular";
import "angular-gettext";
import "restangular";

import shared_props_module from "../shared-properties/shared-properties.js";

import ExecutionService from "./execution-service.js";
import ExecutionRestService from "./execution-rest-service.js";

export default angular
    .module("execution-collection", ["gettext", "restangular", shared_props_module])
    .service("ExecutionService", ExecutionService)
    .service("ExecutionRestService", ExecutionRestService)
    .constant("ExecutionConstants", {
        UNCATEGORIZED: "Uncategorized",
    }).name;

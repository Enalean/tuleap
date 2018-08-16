import angular from "angular";

import GraphDirective from "./diagram-directive.js";
import DiagramRestService from "./diagram-rest-service.js";

export default angular
    .module("reports-modal", [])
    .service("DiagramRestService", DiagramRestService)
    .directive("graph", GraphDirective).name;

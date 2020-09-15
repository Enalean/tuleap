import angular from "angular";

import DefinitionService from "./definition-service.js";

export default angular.module("definition", []).service("DefinitionService", DefinitionService)
    .name;

import angular from "angular";

import "restangular";

import DefinitionService from "./definition-service.js";

export default angular
    .module("definition", ["restangular"])
    .service("DefinitionService", DefinitionService).name;

import angular from "angular";

import UUIDGeneratorService from "./uuid-generator-service.js";

export default angular
    .module("uuid-generator", [])
    .service("UUIDGeneratorService", UUIDGeneratorService).name;

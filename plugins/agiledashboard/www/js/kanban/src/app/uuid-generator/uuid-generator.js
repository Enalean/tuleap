import angular from "angular";
import UUIDGeneratorService from "./uuid-generator-service.js";

angular.module("uuid-generator", []).service("UUIDGeneratorService", UUIDGeneratorService);

export default "uuid-generator";

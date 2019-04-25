import angular from "angular";
import angular_jwt from "angular-jwt";

import "restangular";

import JWTService from "./jwt-service.js";

export default angular.module("jwt", [angular_jwt, "restangular"]).service("JWTService", JWTService)
    .name;

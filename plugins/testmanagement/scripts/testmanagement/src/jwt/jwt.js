import angular from "angular";
import angular_jwt from "angular-jwt";

import JWTService from "./jwt-service.js";

export default angular.module("jwt", [angular_jwt]).service("JWTService", JWTService).name;

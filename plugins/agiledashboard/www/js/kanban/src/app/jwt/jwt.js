import angular from "angular";
import "angular-jwt";

import JWTService from "./jwt-service.js";

angular.module("jwt", ["angular-jwt"]).service("JWTService", JWTService);

export default "jwt";

import angular from "angular";
import jwt from "../jwt/jwt";
import "angular-locker";
import "angular-gettext";
import MercureConfig from "./mercure-config";
import MercureService from "./mercure-service";
angular
    .module("mercure", [jwt, "angular-locker", "gettext"])
    .config(MercureConfig)
    .service("MercureService", MercureService);
export default "mercure";

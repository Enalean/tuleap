import angular from "angular";
import "restangular";

import UserPreferencesService from "./user-preferences-service.js";

export default angular
    .module("user-preferences", ["restangular"])
    .service("UserPreferencesService", UserPreferencesService).name;

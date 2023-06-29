import angular from "angular";
import UserPreferencesService from "./user-preferences-service.js";

angular.module("user-preferences", []).service("UserPreferencesService", UserPreferencesService);

export default "user-preferences";

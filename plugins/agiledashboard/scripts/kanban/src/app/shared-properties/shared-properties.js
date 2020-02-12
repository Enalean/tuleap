import angular from "angular";
import SharedPropertiesService from "./shared-properties-service.js";

angular.module("shared-properties", []).service("SharedPropertiesService", SharedPropertiesService);

export default "shared-properties";

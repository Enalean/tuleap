import angular from "angular";

import SharedPropertiesService from "./shared-properties-service.js";

export default angular
    .module("sharedProperties", [])
    .service("SharedPropertiesService", SharedPropertiesService).name;

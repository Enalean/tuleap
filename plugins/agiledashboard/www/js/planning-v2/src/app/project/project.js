import angular from "angular";
import "restangular";

import ProjectService from "./project-service.js";

export default angular.module("project", ["restangular"]).service("ProjectService", ProjectService)
    .name;

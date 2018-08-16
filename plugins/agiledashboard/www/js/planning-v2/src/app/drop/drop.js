import angular from "angular";

import project from "../project/project.js";
import milestone_rest from "../milestone-rest/milestone-rest.js";
import rest_error from "../rest-error/rest-error.js";

import DroppedService from "./dropped-service.js";

export default angular
    .module("drop", [project, milestone_rest, rest_error])
    .service("DroppedService", DroppedService).name;

import angular from "angular";

import backlog_item from "../backlog-item/backlog-item.js";
import milestone_collection from "../milestone-collection/milestone-collection.js";

import BacklogService from "./backlog-service.js";

export default angular
    .module("backlog-rest", [backlog_item, milestone_collection])
    .service("BacklogService", BacklogService).name;

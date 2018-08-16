import angular from "angular";
import "restangular";

import backlog_item_rest from "../backlog-item-rest/backlog-item-rest.js";

import MilestoneService from "./milestone-service.js";

export default angular
    .module("milestone-rest", ["restangular", backlog_item_rest])
    .service("MilestoneService", MilestoneService).name;

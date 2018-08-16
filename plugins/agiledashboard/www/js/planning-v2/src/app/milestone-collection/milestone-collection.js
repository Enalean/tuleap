import angular from "angular";

import backlog_item_collection from "../backlog-item-collection/backlog-item-collection.js";
import milestone_rest from "../milestone-rest/milestone-rest.js";

import MilestoneCollectionService from "./milestone-collection-service.js";

export default angular
    .module("milestone-collection", [backlog_item_collection, milestone_rest])
    .service("MilestoneCollectionService", MilestoneCollectionService).name;

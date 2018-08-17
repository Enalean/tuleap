import angular from "angular";
import dragular from "dragular";

import backlog_item_rest from "../backlog-item-rest/backlog-item-rest.js";
import backlog_item_selected from "../backlog-item-selected/backlog-item-selected.js";
import backlog_rest from "../backlog-rest/backlog-rest.js";
import drop from "../drop/drop.js";

import MilestoneDirective from "./milestone-directive.js";

export default angular
    .module("milestone", [dragular, backlog_item_rest, backlog_item_selected, backlog_rest, drop])
    .directive("milestone", MilestoneDirective).name;

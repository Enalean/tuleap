import angular from "angular";
import dragular from "dragular";
import angular_artifact_modal_module from "angular-artifact-modal";

import "angular-gettext";

import drop from "../drop/drop.js";
import backlog_item_selected from "../backlog-item-selected/backlog-item-selected.js";
import backlog_item_details from "./backlog-item-details/backlog-item-details.js";
import backlog_item_rest from "../backlog-item-rest/backlog-item-rest.js";

import BacklogItemDirective from "./backlog-item-directive.js";

export default angular
    .module("backlog-item", [
        "gettext",
        angular_artifact_modal_module,
        backlog_item_details,
        backlog_item_rest,
        backlog_item_selected,
        dragular,
        drop
    ])
    .directive("backlogItem", BacklogItemDirective).name;

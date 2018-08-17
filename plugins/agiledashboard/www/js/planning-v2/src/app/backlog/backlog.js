import angular from "angular";
import dragular from "dragular";
import "angular-gettext";

import backlog_item from "../backlog-item/backlog-item.js";
import backlog_item_collection from "../backlog-item-collection/backlog-item-collection.js";
import backlog_item_selected from "../backlog-item-selected/backlog-item-selected.js";
import backlog_rest from "../backlog-rest/backlog-rest.js";
import drop from "../drop/drop.js";
import infinite_scroll from "../infinite-scroll/infinite-scroll.js";
import milestone_collection from "../milestone-collection/milestone-collection.js";
import milestone_rest from "../milestone-rest/milestone-rest.js";
import project from "../project/project.js";
import shared_properties from "../shared-properties/shared-properties.js";
import animator_module from "../animator/animator.js";

import BacklogDirective from "./backlog-directive.js";

export default angular
    .module("backlog", [
        "gettext",
        dragular,
        animator_module,
        backlog_item,
        backlog_item_collection,
        backlog_item_selected,
        backlog_rest,
        drop,
        infinite_scroll,
        milestone_collection,
        milestone_rest,
        project,
        shared_properties
    ])
    .directive("backlog", BacklogDirective).name;

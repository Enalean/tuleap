import angular from "angular";
import "angular-gettext";

import backlog_item_rest from "../../backlog-item-rest/backlog-item-rest.js";
import backlog_item_collection from "../../backlog-item-collection/backlog-item-collection.js";
import edit_item from "../../edit-item/edit-item.js";
import card_fields from "card-fields/index.js";

import BacklogItemDetailsDirective from "./backlog-item-details-directive.js";

export default angular
    .module("backlog-item-details", [
        "gettext",
        backlog_item_rest,
        backlog_item_collection,
        card_fields,
        edit_item
    ])
    .directive("backlogItemDetails", BacklogItemDetailsDirective).name;

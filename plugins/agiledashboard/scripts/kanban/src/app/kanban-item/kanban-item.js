import angular from "angular";
import "angular-gettext";
import ngSanitize from "angular-sanitize";

import card_fields from "@tuleap/plugin-cardwall-card-fields";

import KanbanItemDirective from "./kanban-item-directive.js";
import TimeInfoComponent from "./time-info/time-info-component.js";

export default angular
    .module("kanban-item", ["gettext", ngSanitize, card_fields])
    .directive("kanbanItem", KanbanItemDirective)
    .component("timeInfo", TimeInfoComponent).name;

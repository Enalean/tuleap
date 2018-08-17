import angular from "angular";

import BacklogItemSelectDirective from "./backlog-item-select-directive.js";
import BacklogItemSelectedBarDirective from "./backlog-item-selected-bar-directive.js";
import BacklogItemSelectedService from "./backlog-item-selected-service.js";

export default angular
    .module("backlog-item-selected", [])
    .directive("backlogItemSelect", BacklogItemSelectDirective)
    .directive("backlogItemSelectedBar", BacklogItemSelectedBarDirective)
    .service("BacklogItemSelectedService", BacklogItemSelectedService).name;

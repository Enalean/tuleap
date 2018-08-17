import angular from "angular";

import BacklogItemService from "./backlog-item-service.js";
import BacklogItemFactory from "./backlog-item-factory.js";

export default angular
    .module("backlog-item-rest", ["restangular"])
    .service("BacklogItemService", BacklogItemService)
    .factory("BacklogItemFactory", BacklogItemFactory).name;

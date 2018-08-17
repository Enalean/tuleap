import angular from "angular";

import ItemAnimatorService from "./item-animator-service.js";

export default angular.module("animator", []).service("ItemAnimatorService", ItemAnimatorService)
    .name;

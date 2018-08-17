import angular from "angular";

import infiniteScrollDirective from "./infinite-scroll-directive.js";

export default angular
    .module("infinite-scroll", [])
    .directive("infiniteScroll", infiniteScrollDirective).name;

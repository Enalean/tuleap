import angular from "angular";

import GraphConfig from "./graph-config.js";
import GraphCtrl from "./graph-controller.js";

export default angular.module("graph", []).config(GraphConfig).controller("GraphCtrl", GraphCtrl)
    .name;

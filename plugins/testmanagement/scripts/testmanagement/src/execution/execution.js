import angular from "angular";

import ui_router from "angular-ui-router";
import angular_artifact_modal from "../../../../../tracker/scripts/angular-artifact-modal/index.js";
import angular_tlp from "angular-tlp";

import "angular-gettext";

import execution_collection_module from "../execution-collection/execution-collection.js";
import shared_props_module from "../shared-properties/shared-properties.js";
import definition_module from "../definition/definition.js";
import artifact_links_module from "../artifact-links-graph/artifact-links-graph.js";
import socket_module from "../socket/socket.js";
import campaign_module from "../campaign/campaign.js";

import ExecutionConfig from "./execution-config.js";
import ExecutionListCtrl from "./execution-list-controller.js";
import ExecutionDetailCtrl from "./execution-detail-controller.js";
import ExecutionListFilter from "./execution-list-filter.js";
import AutomatedTestsFilter from "./automated-tests-filter.js";
import ExecutionListHeader from "./execution-list-header/execution-list-header-component.js";
import ExecutionDetailJustUpdated from "./execution-detail-just-updated-component.js";
import ExecutionWithSteps from "./execution-with-steps/execution-with-steps-component.js";
import Step from "./execution-with-steps/step/step-component.js";
import RobotSvgDisplayer from "./svg/robot-svg-displayer-component.js";

export default angular
    .module("execution", [
        "gettext",
        angular_artifact_modal,
        angular_tlp,
        artifact_links_module,
        campaign_module,
        definition_module,
        execution_collection_module,
        shared_props_module,
        socket_module,
        ui_router,
    ])
    .config(ExecutionConfig)
    .controller("ExecutionListCtrl", ExecutionListCtrl)
    .controller("ExecutionDetailCtrl", ExecutionDetailCtrl)
    .component("executionDetailJustUpdated", ExecutionDetailJustUpdated)
    .component("executionListHeader", ExecutionListHeader)
    .component("executionWithSteps", ExecutionWithSteps)
    .component("step", Step)
    .component("robotSvgDisplayer", RobotSvgDisplayer)
    .filter("ExecutionListFilter", ExecutionListFilter)
    .filter("AutomatedTestsFilter", AutomatedTestsFilter).name;

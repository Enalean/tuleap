import angular from "angular";

import ui_router from "@uirouter/angularjs";
import angular_artifact_modal from "@tuleap/plugin-tracker-artifact-modal";
import angular_tlp from "@tuleap/angular-tlp";

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
import ExecutionAttachments from "./execution-attachments/execution-attachments-component.js";
import ExecutionAttachmentsCreationErrorModal from "./execution-attachments/execution-attachments-creation-error-modal.js";
import ExecutionAttachmentsDropZone from "./execution-attachments/execution-attachments-drop-zone.js";
import ExecutionAttachmentsDropZoneMessage from "./execution-attachments/execution-attachments-drop-zone-message.js";
import { loadOnlyTooltipOnAnchorElement, loadTooltips } from "@tuleap/tooltip";

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
    .component("executionAttachments", ExecutionAttachments)
    .component("executionAttachmentsCreationErrorModal", ExecutionAttachmentsCreationErrorModal)
    .component("executionAttachmentsDropZoneMessage", ExecutionAttachmentsDropZoneMessage)
    .component("executionDetailJustUpdated", ExecutionDetailJustUpdated)
    .component("executionListHeader", ExecutionListHeader)
    .component("executionWithSteps", ExecutionWithSteps)
    .component("step", Step)
    .component("robotSvgDisplayer", RobotSvgDisplayer)
    .directive("executionAttachmentsDropZone", ExecutionAttachmentsDropZone)
    .directive("loadTooltip", () => {
        return {
            restrict: "A",
            link: function (scope, element) {
                loadOnlyTooltipOnAnchorElement(element[0]);
            },
        };
    })
    .directive("loadAllTooltips", () => {
        return {
            restrict: "A",
            scope: {
                element: "=",
            },
            link: function (scope, element) {
                scope.$watch("element", () => {
                    loadTooltips(element[0]);
                });
            },
        };
    })
    .filter("ExecutionListFilter", ExecutionListFilter)
    .filter("AutomatedTestsFilter", AutomatedTestsFilter).name;

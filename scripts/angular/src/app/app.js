import angular from "angular";
import ngSanitize from "angular-sanitize";
import ui_router from "angular-ui-router";
import angular_artifact_modal from "angular-artifact-modal";
import angular_tlp from "angular-tlp";

import "angular-breadcrumb";
import "angular-gettext";
import "../../po/fr.po";
import "angular-moment";
import "d3-selection-multi";

import shared_properties from "./shared-properties/shared-properties.js";
import uuid_generator from "./uuid-generator/uuid-generator.js";
import socket from "./socket/socket.js";
import jwt from "./jwt/jwt.js";
import campaign from "./campaign/campaign.js";
import execution from "./execution/execution.js";
import definition from "./definition/definition.js";
import graph from "./graph/graph.js";
import artifact_links_graph from "./artifact-links-graph/artifact-links-graph.js";

import TestManagementConfig from "./app-config.js";
import AutoFocusDirective from "./app-directive.js";
import InPropertiesFilter from "./app-filter.js";
import TestManagementCtrl from "./app-controller.js";
import EnableTlpTableFilterDirective from "./enable-tlp-table-filter-directive.js";
import OpenTlpDropdownDirective from "./open-tlp-dropdown-directive.js";
import FeedbackComponent from "./feedback-component.js";

export default angular
    .module("testmanagement", [
        ngSanitize,
        ui_router,
        angular_artifact_modal,
        angular_tlp,
        "ncy-angular-breadcrumb",
        "gettext",
        "angularMoment",
        shared_properties,
        uuid_generator,
        socket,
        jwt,
        campaign,
        execution,
        definition,
        graph,
        artifact_links_graph
    ])
    .config(TestManagementConfig)
    .directive("autoFocus", AutoFocusDirective)
    .directive("enableTlpTableFilter", EnableTlpTableFilterDirective)
    .directive("openTlpDropdown", OpenTlpDropdownDirective)
    .filter("InPropertiesFilter", InPropertiesFilter)
    .controller("TestManagementCtrl", TestManagementCtrl)
    .component("feedbackMessage", FeedbackComponent).name;

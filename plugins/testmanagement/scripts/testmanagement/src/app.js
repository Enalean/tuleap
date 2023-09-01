/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import angular from "angular";
import ngSanitize from "angular-sanitize";
import ui_router from "@uirouter/angularjs";
import angular_artifact_modal from "@tuleap/plugin-tracker-artifact-modal";
import angular_tlp from "@tuleap/angular-tlp";
import angular_moment from "angular-moment";
import angular_filter from "angular-filter";

import "angular-breadcrumb";
import "angular-gettext";
import translations from "../po/fr_FR.po";

import shared_properties from "./shared-properties/shared-properties.js";
import uuid_generator from "./uuid-generator/uuid-generator.js";
import socket from "./socket/socket.js";
import jwt from "./jwt/jwt.js";
import campaign from "./campaign/campaign.js";
import execution from "./execution/execution.js";
import definition from "./definition/definition.js";
import graph from "./graph/graph.js";
import artifact_links_graph from "./artifact-links-graph/artifact-links-graph.js";
import mercure from "./realtime/mercure";

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
        angular_moment,
        angular_filter,
        shared_properties,
        uuid_generator,
        socket,
        jwt,
        campaign,
        execution,
        definition,
        graph,
        artifact_links_graph,
        mercure,
    ])
    .config(TestManagementConfig)
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(translations)) {
                gettextCatalog.setStrings(language, strings);
            }
        },
    ])
    .directive("autoFocus", AutoFocusDirective)
    .directive("enableTlpTableFilter", EnableTlpTableFilterDirective)
    .directive("openTlpDropdown", OpenTlpDropdownDirective)
    .filter("InPropertiesFilter", InPropertiesFilter)
    .controller("TestManagementCtrl", TestManagementCtrl)
    .component("feedbackMessage", FeedbackComponent).name;

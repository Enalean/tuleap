/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
import angular_sanitize from "angular-sanitize";
import dragular from "dragular";
import angular_artifact_modal from "@tuleap/plugin-tracker-artifact-modal";
import angular_moment from "angular-moment";
import angular_tlp from "@tuleap/angular-tlp";
import angular_async from "@tuleap/angular-async";
import "angular-locker";
import "angular-gettext";
import angular_jwt from "angular-jwt";
import "angular-socket-io"; // provides btford.socket-io
import { card_fields } from "@tuleap/plugin-cardwall-card-fields";
import translations from "../../po/fr_FR.po";
import SocketConfig from "./socket/socket-config.js";
import MercureConfig from "./realtime/mercure-config";
import ErrorCtrl from "./error-modal/error-controller.js";
import MainCtrl from "./app-main-controller.js";
import KanbanService from "./kanban-service.js";
import ColumnCollectionService from "./column-collection-service.js";
import DroppedService from "./dropped-service.js";
import KanbanFilterValue from "./filter-value.js";
import AddInPlaceDirective from "./add-in-place/add-in-place-directive.js";
import AddToDashboardDirective from "./add-to-dashboard/add-to-dashboard-directive.js";
import FilterTrackerReportDirective from "./filter-tracker-report/filter-tracker-report-directive.js";
import GoToKanbanDirective from "./go-to-kanban/go-to-kanban-directive.js";
import EscKeyDirective from "./esc-key/esc-key-directive.js";
import KanbanFilteredUpdatedAlertDirective from "./kanban-filtered-updated-alert/kanban-filtered-updated-alert-directive.js";
import InPropertiesFilter from "./in-properties-filter/in-properties-filter.js";
import KanbanColumnDirective from "./kanban-column/kanban-column-directive.js";
import KanbanColumnService from "./kanban-column/kanban-column-service.js";
import KanbanItemRestService from "./kanban-item/kanban-item-rest-service.js";
import KanbanFilteredUpdatedAlertService from "./kanban-filtered-updated-alert/kanban-filtered-updated-alert-service.js";
import FilterTrackerReportController from "./filter-tracker-report/filter-tracker-report-controller.js";
import FilterTrackerReportService from "./filter-tracker-report/filter-tracker-report-service.js";
import WipPopoverDirective from "./wip-popover/wip-popover-directive.js";
import KanbanColumnController from "./kanban-column/kanban-column-controller.js";
import KanbanDirective from "./kanban-directive.js";
import ColumnWipHeaderDirective from "./kanban-column/column-wip-header/column-wip-header-directive.js";
import FeedbackComponent from "./feedback-component.js";
import UnderTheFoldNotificationComponent from "./under-the-fold-notification-component.js";
import JWTService from "./jwt/jwt-service.js";
import SocketFactory from "./socket/socket-factory.js";
import SocketService from "./socket/socket-service.js";
import RestErrorService from "./error-modal/rest-error-service.js";
import KanbanItemDirective from "./kanban-item/kanban-item-directive.js";
import TimeInfoComponent from "./kanban-item/time-info/time-info-component.js";
import UUIDGeneratorService from "./uuid-generator/uuid-generator-service.js";
import SharedPropertiesService from "./shared-properties/shared-properties-service.js";
import UserPreferencesService from "./user-preferences/user-preferences-service.js";
import MercureService from "./realtime/mercure-service.js";

export default angular
    .module("kanban", [
        angular_moment,
        "angular-locker",
        "gettext",
        angular_sanitize,
        angular_jwt,
        "btford.socket-io",
        dragular,
        angular_artifact_modal,
        angular_async,
        angular_tlp,
        card_fields,
    ])
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(translations)) {
                gettextCatalog.setStrings(language, strings);
            }
        },
    ])
    .config(SocketConfig)
    .config(MercureConfig)
    .controller("MainCtrl", MainCtrl)
    .controller("FilterTrackerReportController", FilterTrackerReportController)
    .controller("KanbanColumnController", KanbanColumnController)
    .controller("ErrorCtrl", ErrorCtrl)
    .service("KanbanService", KanbanService)
    .service("ColumnCollectionService", ColumnCollectionService)
    .service("DroppedService", DroppedService)
    .service("KanbanColumnService", KanbanColumnService)
    .service("KanbanItemRestService", KanbanItemRestService)
    .service("FilterTrackerReportService", FilterTrackerReportService)
    .service("KanbanFilteredUpdatedAlertService", KanbanFilteredUpdatedAlertService)
    .service("JWTService", JWTService)
    .service("SocketFactory", SocketFactory)
    .service("SocketService", SocketService)
    .service("RestErrorService", RestErrorService)
    .service("UUIDGeneratorService", UUIDGeneratorService)
    .service("SharedPropertiesService", SharedPropertiesService)
    .service("UserPreferencesService", UserPreferencesService)
    .service("MercureService", MercureService)
    .directive("kanban", KanbanDirective)
    .directive("addInPlace", AddInPlaceDirective)
    .directive("addToDashboard", AddToDashboardDirective)
    .directive("filterTrackerReport", FilterTrackerReportDirective)
    .directive("escKey", EscKeyDirective)
    .directive("kanbanColumn", KanbanColumnDirective)
    .directive("wipPopover", WipPopoverDirective)
    .directive("goToKanban", GoToKanbanDirective)
    .directive("kanbanFilteredUpdatedAlert", KanbanFilteredUpdatedAlertDirective)
    .directive("columnWipHeader", ColumnWipHeaderDirective)
    .directive("kanbanItem", KanbanItemDirective)
    .value("KanbanFilterValue", KanbanFilterValue)
    .filter("InPropertiesFilter", InPropertiesFilter)
    .component("underTheFoldNotification", UnderTheFoldNotificationComponent)
    .component("feedbackMessage", FeedbackComponent)
    .component("timeInfo", TimeInfoComponent).name;

const kanban_elements = document.getElementsByClassName("widget-kanban");
for (const element of kanban_elements) {
    angular.bootstrap(element, ["kanban"]);
}

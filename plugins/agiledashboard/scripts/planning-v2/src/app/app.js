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
import ngAnimate from "angular-animate";
import ngSanitize from "angular-sanitize";
import dragular from "dragular";
import angular_artifact_modal from "@tuleap/plugin-tracker-artifact-modal";
import "@tuleap/plugin-agiledashboard-scrum-milestone-header";

import "angular-moment";
import "moment/locale/fr.js";
import "angular-gettext";
import translations from "../../po/fr_FR.po";

import card_fields from "@tuleap/plugin-cardwall-card-fields";

import MainController from "./main-controller.js";
import PlanningConfig from "./app-config.js";
import OpenTlpDropdownDirective from "./open-tlp-dropdown-directive.js";
import SuccessMessageComponent from "./success-message-component.js";
import ItemProgressComponent from "./item-progress/item-progress-component.js";
import GlobalErrorComponent from "./rest-error/global-error-component";
import PlannerAppComponent from "./planner-app-component";
import PlannerViewComponent from "./planner-view/planner-view-component";
import BacklogItemService from "./backlog-item-rest/backlog-item-service.js";
import BacklogItemCollectionService from "./backlog-item-collection/backlog-item-collection-service.js";
import MilestoneCollectionService from "./milestone-collection/milestone-collection-service.js";
import MilestoneDirective from "./milestone/milestone-directive.js";
import MilestoneService from "./milestone-rest/milestone-service.js";
import RestErrorService from "./rest-error/rest-error-service.js";
import BacklogItemSelectDirective from "./backlog-item-selected/backlog-item-select-directive.js";
import BacklogItemSelectedBarDirective from "./backlog-item-selected/backlog-item-selected-bar-directive.js";
import BacklogItemSelectedService from "./backlog-item-selected/backlog-item-selected-service.js";
import BacklogItemDetailsDirective from "./backlog-item/backlog-item-details/backlog-item-details-directive.js";
import CardParentLinkDirective from "./backlog-item/card-parent-link/card-parent-link-directive.js";
import SharedPropertiesService from "./shared-properties/shared-properties-service.js";
import UserPreferencesService from "./user-preferences/user-preferences-service.js";
import DroppedService from "./drop/dropped-service.js";
import ProjectService from "./project/project-service.js";
import infiniteScrollDirective from "./infinite-scroll/infinite-scroll-directive.js";
import InPropertiesFilter from "./in-properties/in-properties-filter.js";
import EditItemService from "./edit-item/edit-item-service.js";
import ItemAnimatorService from "./animator/item-animator-service.js";
import BacklogDirective from "./backlog/backlog-directive.js";
import BacklogItemDirective from "./backlog-item/backlog-item-directive.js";
import BacklogService from "./backlog-rest/backlog-service.js";
import { ErrorState } from "./rest-error/ErrorState";

export default angular
    .module("planning", [
        "angularMoment",
        "gettext",
        angular_artifact_modal,
        ngAnimate,
        ngSanitize,
        dragular,
        card_fields,
    ])
    .config(PlanningConfig)
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(translations)) {
                gettextCatalog.setStrings(language, strings);
            }
        },
    ])
    .controller("MainController", MainController)
    .directive("backlog", BacklogDirective)
    .directive("backlogItem", BacklogItemDirective)
    .directive("openTlpDropdown", OpenTlpDropdownDirective)
    .directive("milestone", MilestoneDirective)
    .directive("backlogItemSelect", BacklogItemSelectDirective)
    .directive("backlogItemSelectedBar", BacklogItemSelectedBarDirective)
    .directive("backlogItemDetails", BacklogItemDetailsDirective)
    .directive("cardParentLink", CardParentLinkDirective)
    .directive("infiniteScroll", infiniteScrollDirective)
    .service("BacklogItemService", BacklogItemService)
    .service("MilestoneCollectionService", MilestoneCollectionService)
    .service("BacklogItemCollectionService", BacklogItemCollectionService)
    .service("MilestoneService", MilestoneService)
    .service("RestErrorService", RestErrorService)
    .service("BacklogItemSelectedService", BacklogItemSelectedService)
    .service("SharedPropertiesService", SharedPropertiesService)
    .service("UserPreferencesService", UserPreferencesService)
    .service("DroppedService", DroppedService)
    .service("ProjectService", ProjectService)
    .service("EditItemService", EditItemService)
    .service("ItemAnimatorService", ItemAnimatorService)
    .service("BacklogService", BacklogService)
    .service("BacklogItemService", BacklogItemService)
    .service("MilestoneService", MilestoneService)
    .component("successMessage", SuccessMessageComponent)
    .component("itemProgress", ItemProgressComponent)
    .component("globalError", GlobalErrorComponent)
    .component("plannerApp", PlannerAppComponent)
    .component("plannerView", PlannerViewComponent)
    .filter("InPropertiesFilter", InPropertiesFilter)
    .value("ErrorState", new ErrorState()).name;

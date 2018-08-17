import "../../../scrum-header.js";

import angular from "angular";
import ngAnimate from "angular-animate";
import ngSanitize from "angular-sanitize";
import angular_artifact_modal_module from "angular-artifact-modal";

import "angular-moment";
import "moment/locale/fr.js";
import "angular-gettext";
import "restangular";
import "../../po/fr.po";

import backlog from "./backlog/backlog.js";
import backlog_item_rest from "./backlog-item-rest/backlog-item-rest.js";
import backlog_item_selected from "./backlog-item-selected/backlog-item-selected.js";
import edit_item from "./edit-item/edit-item.js";
import in_properties from "./in-properties/in-properties.js";
import milestone from "./milestone/milestone.js";
import shared_properties from "./shared-properties/shared-properties.js";
import user_preferences from "./user-preferences/user-preferences.js";
import rest_error from "./rest-error/rest-error.js";
import animator_module from "./animator/animator.js";

import MainController from "./main-controller.js";
import PlanningConfig from "./app-config.js";
import PlanningDirective from "./planning-directive.js";
import OpenTlpDropdownDirective from "./open-tlp-dropdown-directive.js";
import SuccessMessageComponent from "./success-message-component.js";
import ItemProgressComponent from "./item-progress/item-progress-component.js";

export default angular
    .module("planning", [
        "angularMoment",
        "gettext",
        angular_artifact_modal_module,
        ngAnimate,
        ngSanitize,
        animator_module,
        backlog,
        backlog_item_rest,
        backlog_item_selected,
        edit_item,
        in_properties,
        milestone,
        rest_error,
        shared_properties,
        user_preferences
    ])
    .config(PlanningConfig)
    .controller("MainController", MainController)
    .directive("planning", PlanningDirective)
    .directive("openTlpDropdown", OpenTlpDropdownDirective)
    .component("successMessage", SuccessMessageComponent)
    .component("itemProgress", ItemProgressComponent).name;

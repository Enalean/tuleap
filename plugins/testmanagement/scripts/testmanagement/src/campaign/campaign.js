import angular from "angular";
import ui_router from "angular-ui-router";
import shared_properties_module from "../shared-properties/shared-properties.js";
import definition_module from "../definition/definition.js";
import angular_tlp_module from "angular-tlp";

import "restangular";
import "angular-gettext";

import CampaignConfig from "./campaign-config.js";
import CampaignService from "./campaign-service.js";
import CampaignCtrl from "./campaign-controller.js";
import CampaignListCtrl from "./campaign-list-controller.js";
import CampaignNewCtrl from "./campaign-new-controller.js";
import CurrentPageFilter from "./campaign-new-filter.js";

export default angular
    .module("campaign", [
        ui_router,
        "restangular",
        "gettext",
        angular_tlp_module,
        definition_module,
        shared_properties_module,
    ])
    .config(CampaignConfig)
    .service("CampaignService", CampaignService)
    .controller("CampaignCtrl", CampaignCtrl)
    .controller("CampaignListCtrl", CampaignListCtrl)
    .controller("CampaignNewCtrl", CampaignNewCtrl)
    .filter("CurrentPageFilter", CurrentPageFilter)
    .constant("CampaignEditConstants", {
        SELECTION_STATES: {
            unselected: "unselected",
            selected: "selected",
            added: "added",
            removed: "removed",
            all: "all",
            some: "some",
            none: "none",
        },
    }).name;

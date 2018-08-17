import angular from "angular";
import angular_artifact_modal_module from "angular-artifact-modal";

import shared_properties from "../shared-properties/shared-properties.js";
import milestone_rest from "../milestone-rest/milestone-rest.js";

import EditItemService from "./edit-item-service.js";

export default angular
    .module("edit-item", [angular_artifact_modal_module, milestone_rest, shared_properties])
    .service("EditItemService", EditItemService).name;

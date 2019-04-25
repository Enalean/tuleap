import angular from "angular";

import AwkwardCreationFields from "./awkward-creation-fields-constant.js";
import { STRUCTURAL_FIELDS } from "../../../constants/fields-constants.js";
import FieldValuesService from "./field-values-service.js";
import TrackerTransformerService from "./tracker-transformer-service.js";

angular
    .module("tuleap-artifact-modal-model", [])
    .constant("TuleapArtifactModalAwkwardCreationFields", AwkwardCreationFields)
    .constant("TuleapArtifactModalStructuralFields", STRUCTURAL_FIELDS)
    .service("TuleapArtifactFieldValuesService", FieldValuesService)
    .service("TuleapArtifactModalTrackerTransformerService", TrackerTransformerService);

export default "tuleap-artifact-modal-model";

import angular from "angular";

import AwkwardCreationFields from "./awkward-creation-fields-constant.js";
import { STRUCTURAL_FIELDS } from "../../../constants/fields-constants.js";
import FieldValuesService from "./field-values-service.js";
import FormTreeBuilderService from "./form-tree-builder-service.js";
import TrackerTransformerService from "./tracker-transformer-service.js";
import WorkflowService from "./workflow-service.js";

angular
    .module("tuleap-artifact-modal-model", [])
    .constant("TuleapArtifactModalAwkwardCreationFields", AwkwardCreationFields)
    .constant("TuleapArtifactModalStructuralFields", STRUCTURAL_FIELDS)
    .service("TuleapArtifactFieldValuesService", FieldValuesService)
    .service("TuleapArtifactModalFormTreeBuilderService", FormTreeBuilderService)
    .service("TuleapArtifactModalTrackerTransformerService", TrackerTransformerService)
    .service("TuleapArtifactModalWorkflowService", WorkflowService);

export default "tuleap-artifact-modal-model";

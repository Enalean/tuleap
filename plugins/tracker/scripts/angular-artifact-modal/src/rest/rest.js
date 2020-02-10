import angular from "angular";
import RestService from "./rest-service.js";

angular
    .module("tuleap-artifact-modal-rest", [])
    .service("TuleapArtifactModalRestService", RestService);

export default "tuleap-artifact-modal-rest";

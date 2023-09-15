import angular from "angular";

import ArtifactLinksModelService from "./artifact-links-model-service.js";
import ArtifactLinksGraphRestService from "./artifact-links-graph-rest-service.js";
import ArtifactLinksGraphService from "./artifact-links-graph-service.js";
import ArtifactLinksGraphCtrl from "./artifact-links-graph-controller.js";
import GraphDirective from "./artifact-links-graph-directive.js";

export default angular
    .module("tuleap.artifact-links-graph", [])
    .service("ArtifactLinksModelService", ArtifactLinksModelService)
    .service("ArtifactLinksGraphRestService", ArtifactLinksGraphRestService)
    .service("ArtifactLinksGraphService", ArtifactLinksGraphService)
    .controller("ArtifactLinksGraphCtrl", ArtifactLinksGraphCtrl)
    .directive("graph", GraphDirective)
    .value("ArtifactLinksArtifactsList", {
        artifacts: new Map(),
    })
    .value("ArtifactLinksGraphModalLoading", {
        loading: {
            is_loading: false,
        },
    }).name;

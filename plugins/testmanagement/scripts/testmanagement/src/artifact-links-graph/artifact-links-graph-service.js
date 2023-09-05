import { extend } from "lodash-es";
import "./artifact-links-graph.tpl.html";

export default ArtifactLinksGraphService;

ArtifactLinksGraphService.$inject = [
    "TlpModalService",
    "ArtifactLinksGraphModalLoading",
    "ArtifactLinksGraphRestService",
    "ArtifactLinksModelService",
];

function ArtifactLinksGraphService(
    TlpModalService,
    ArtifactLinksGraphModalLoading,
    ArtifactLinksGraphRestService,
    ArtifactLinksModelService,
) {
    var self = this;

    extend(self, {
        showGraphModal: showGraphModal,
        showGraph: showGraph,
    });

    function showGraphModal(execution) {
        ArtifactLinksGraphModalLoading.loading.is_loading = true;

        return TlpModalService.open({
            templateUrl: "artifact-links-graph.tpl.html",
            controller: "ArtifactLinksGraphCtrl",
            controllerAs: "modal",
            resolve: {
                modal_model: self.showGraph(execution.id),
            },
        });
    }

    function showGraph(artifact_id) {
        return ArtifactLinksGraphRestService.getArtifactGraph(artifact_id).then(
            function (artifact) {
                return ArtifactLinksModelService.getGraphStructure(artifact);
            },
        );
    }
}

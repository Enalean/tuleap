import _ from 'lodash';
import './artifact-links-graph.tpl.html';

export default ArtifactLinksGraphService;

ArtifactLinksGraphService.$inject = [
    '$modal',
    'ArtifactLinksGraphModalLoading',
    'ArtifactLinksGraphRestService',
    'ArtifactLinksModelService'
];

function ArtifactLinksGraphService(
    $modal,
    ArtifactLinksGraphModalLoading,
    ArtifactLinksGraphRestService,
    ArtifactLinksModelService
) {
    var self = this;

    _.extend(self, {
        showGraphModal: showGraphModal,
        showGraph     : showGraph
    });

    function showGraphModal(execution) {
        ArtifactLinksGraphModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'artifact-links-graph.tpl.html',
            controller : 'ArtifactLinksGraphCtrl as modal',
            resolve: {
                modal_model: function () {
                    return self.showGraph(execution.id);
                }
            }
        });
    }

    function showGraph(artifact_id) {
        return ArtifactLinksGraphRestService.getArtifactGraph(artifact_id).then(function(artifact) {
            return ArtifactLinksModelService.getGraphStructure(artifact);
        });
    }
}


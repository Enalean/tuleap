angular
    .module('tuleap.artifact-links-graph')
    .service('ArtifactLinksGraphService', ArtifactLinksGraphService)
    .value('ArtifactLinksGraphModalLoading', {
        loading: {
            is_loading: false
        }
    });

ArtifactLinksGraphService.$inject = ['$modal', 'ArtifactLinksGraphModalLoading'];

function ArtifactLinksGraphService($modal, ArtifactLinksGraphModalLoading) {
    var self = this;

    _.extend(self, {
        showGraph: showGraph
    });

    function showGraph(artifact_id) {
        ArtifactLinksGraphModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'artifact-links-graph/artifact-links-graph.tpl.html',
            controller : 'ArtifactLinksGraphCtrl as modal',
            resolve: {
                modal_model: function () {
                    return {
                        graph: {
                            links: [
                                { source: 2, target: 5, type: "arrow" },
                                { source: 2, target:  8, type: "arrow" },
                                { source: 5, target:  2, type: "arrow" },
                                { source: 8, target:  2, type: "arrow" }
                            ],
                            nodes: [
                                { id: 2, label: "tracker_shortname #2" },
                                { id: 5, label: "tracker_shortname #5" },
                                { id: 8, label: "tracker_shortname #8" }
                            ]
                        }
                    };
                }
            }
        });
    }
}

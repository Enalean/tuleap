(function () {
    angular
        .module('tuleap.artifact-links-graph')
        .service('ArtifactLinksGraphService', ArtifactLinksGraphService)
        .value('ArtifactLinksGraphModalLoading', {
            loading: {
                is_loading: false
            }
        });

    ArtifactLinksGraphService.$inject = [
        '$modal',
        'ArtifactLinksGraphModalLoading',
        'ArtifactLinksGraphRestService',
        'ArtifactLinksModelService',
        'ArtifactLinksTrackerService',
        '$q'
    ];

    function ArtifactLinksGraphService(
        $modal,
        ArtifactLinksGraphModalLoading,
        ArtifactLinksGraphRestService,
        ArtifactLinksModelService,
        ArtifactLinksTrackerService,
        $q
    ) {
        var self = this;

        _.extend(self, {
            showGraph: showGraph
        });

        function showGraph(execution, definition) {
            ArtifactLinksGraphModalLoading.loading.is_loading = true;

            return $modal.open({
                backdrop   : 'static',
                templateUrl: 'artifact-links-graph/artifact-links-graph.tpl.html',
                controller : 'ArtifactLinksGraphCtrl as modal',
                resolve: {
                    modal_model: function () {
                        var promises = [];
                        promises.push(ArtifactLinksGraphRestService.getArtifact(execution.id));
                        promises.push(ArtifactLinksGraphRestService.getArtifact(definition.id));
                        return $q.all(promises).then(function(results) {
                            var artifacts = constructArtifacts(results[0], results[1]);
                            return ArtifactLinksTrackerService.initializeTrackers(artifacts).then(function(trackers) {
                                return ArtifactLinksModelService.getGraphStructure(artifacts, trackers);
                            });
                        });
                    },
                    title: function() {
                        return definition.summary;
                    }
                }
            });
        }

        function constructArtifacts(execution, definition) {
            var nodes = [];

            if (execution) {
                nodes.push(execution);
            }
            if (definition) {
                nodes.push(definition);
            }

            var artifacts = {
                nodes: nodes,
                current_node: definition ? definition : execution
            };

            return artifacts;
        }
    }
})();
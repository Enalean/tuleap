(function () {
    angular
        .module('tuleap.artifact-links-graph')
        .service('ArtifactLinksModelService', ArtifactLinksModelService)
        .value('ArtifactLinksGraphModalLoading', {
            loading: {
                is_loading: false
            }
        });

    function ArtifactLinksModelService () {
        var self = this;

        _.extend(self, {
            getGraphStructure: getGraphStructure

        });

        function getGraphStructure(artifact) {
            var modal_model = {
                errors: [],
                graph : {
                    links: [],
                    nodes: []
                }
            };

            var outgoing_artifact_links = artifact.links,
                incoming_artifact_links = artifact.reverse_links;

            createNodeForCurrentArtifact(modal_model.graph, artifact);
            createNodesAndLinksForOutgoingLinks(modal_model.graph, artifact, outgoing_artifact_links);
            createNodesAndLinksForIncomingLinks(modal_model.graph, artifact, incoming_artifact_links);

            modal_model.graph.nodes = _.uniq(modal_model.graph.nodes, 'id');

            return modal_model;
        }

        function createNodeForCurrentArtifact(graph, artifact) {
            var current_node = artifact;
            delete current_node.links;
            delete current_node.reverse_links;
            graph.nodes.push(current_node);
        }

        function createNodesAndLinksForOutgoingLinks(graph, artifact, outgoing_links) {
            _(outgoing_links).forEach(function (outgoing_link) {
                var link = {
                    source: artifact.id,
                    target: outgoing_link.id,
                    type: 'arrow'
                };

                graph.links.push(link);
                graph.nodes.push(outgoing_link);
            });
        }

        function createNodesAndLinksForIncomingLinks(graph, artifact, incoming_links) {
            _(incoming_links).forEach(function(incoming_link) {
                var link = {
                    source: incoming_link.id,
                    target: artifact.id,
                    type: 'arrow'
                };

                graph.links.push(link);
                graph.nodes.push(incoming_link);
            });
        }
    }
})();
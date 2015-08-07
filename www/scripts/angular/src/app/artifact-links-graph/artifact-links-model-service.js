angular
    .module('tuleap.artifact-links-graph')
    .service('ArtifactLinksModelService', ArtifactLinksModelService)
    .value('ArtifactLinksArtifactsList', {
        artifacts: {}
    });

ArtifactLinksModelService.$inject = [
    'gettextCatalog',
    'SharedPropertiesService'
];

function ArtifactLinksModelService (
    gettextCatalog,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        getGraphStructure    : getGraphStructure,
        getArtifactLinksField: getArtifactLinksField

    });

    function getGraphStructure(artifacts, trackers) {
        var modal_model = {
            errors: [],
            graph : {
                links: [],
                nodes: []
            }
        };
        _(artifacts.nodes).forEach(function(artifact) {
            var artifact_link_field = getArtifactLinksField(artifact);
            if (! artifact_link_field ) {
                modal_model.errors.push(gettextCatalog.getString('Artifact links field not found.'));
            } else {
                var outgoing_artifact_links = artifact_link_field.links,
                    incoming_artifact_links = artifact_link_field.reverse_links;

                createNodeForCurrentArtifact(modal_model.graph, artifacts.current_node, trackers);
                createNodesAndLinksForOutgoingLinks(modal_model.graph, artifacts.current_node, outgoing_artifact_links, trackers);
                createNodesAndLinksForIncomingLinks(modal_model.graph, artifacts.current_node, incoming_artifact_links, trackers);

                modal_model.graph.nodes = _.uniq(modal_model.graph.nodes, 'id');
            }
        });

        return modal_model;
    }

    function getArtifactLinksField(artifact) {
        return _.find(artifact.values, { type: 'art_link' });
    }

    function createNodeForCurrentArtifact(graph, artifact, trackers) {
        var me = {
            id: artifact.id,
            label: trackers[artifact.tracker.id].item_name + ' #' + artifact.id,
            color_name: trackers[artifact.tracker.id].color_name
        };
        graph.nodes.push(me);
    }

    function createNodesAndLinksForOutgoingLinks(graph, artifact, outgoing_links, trackers) {
        _(outgoing_links).forEach(function (outgoing_link) {
            if (outgoing_link.id !== artifact.id &&
                outgoing_link.tracker.id !== SharedPropertiesService.getTrackerExecutionId()) {

                var link = {
                        source: artifact.id,
                        target: outgoing_link.id,
                        type: 'arrow'
                    },
                    node = {
                        id: outgoing_link.id,
                        label: trackers[outgoing_link.tracker.id].item_name + ' #' + outgoing_link.id,
                        color_name: trackers[outgoing_link.tracker.id].color_name
                    };

                graph.links.push(link);
                graph.nodes.push(node);
            }
        });
    }

    function createNodesAndLinksForIncomingLinks(graph, artifact, incoming_links, trackers) {
        _(incoming_links).forEach(function(incoming_link) {
            if (incoming_link.id !== artifact.id &&
                incoming_link.tracker.id !== SharedPropertiesService.getTrackerExecutionId()) {

                var link = {
                        source: incoming_link.id,
                        target: artifact.id,
                        type: 'arrow'
                    },
                    node = {
                        id: incoming_link.id,
                        label: trackers[incoming_link.tracker.id].item_name + ' #' + incoming_link.id,
                        color_name: trackers[incoming_link.tracker.id].color_name
                    };

                graph.links.push(link);
                graph.nodes.push(node);
            }
        });
    }
}

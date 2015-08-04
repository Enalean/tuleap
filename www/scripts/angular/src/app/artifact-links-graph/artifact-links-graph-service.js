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
    'gettextCatalog',
    'ArtifactLinksGraphModalLoading',
    'ArtifactLinksGraphRestService',
    'SharedPropertiesService'
];

function ArtifactLinksGraphService(
    $modal,
    gettextCatalog,
    ArtifactLinksGraphModalLoading,
    ArtifactLinksGraphRestService,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        showGraph        : showGraph,
        getGraphStructure: getGraphStructure
    });

    function showGraph(execution_id, definition_id) {
        ArtifactLinksGraphModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'artifact-links-graph/artifact-links-graph.tpl.html',
            controller : 'ArtifactLinksGraphCtrl as modal',
            resolve: {
                modal_model: function () {
                    return ArtifactLinksGraphRestService.getArtifact(execution_id).then(function(execution) {
                        return ArtifactLinksGraphRestService.getArtifact(definition_id).then(function(definition) {
                            return self.getGraphStructure(execution, definition);
                        });
                    });
                }
            }
        });
    }

    function getGraphStructure(execution, definition) {
        var modal_model = {
            errors: [],
            graph : {
                links: [],
                nodes: []
            }
        };

        var execution_link_field = getArtifactLinksField(execution);
        var definition_link_field = getArtifactLinksField(definition);

        if (! execution_link_field || ! definition_link_field) {
            modal_model.errors.push(gettextCatalog.getString('Artifact links field not found.'));

        } else {
            var outgoing_execution_links = execution_link_field.links,
                incoming_execution_links = execution_link_field.reverse_links;

            var outgoing_definition_links = definition_link_field.links,
                incoming_definition_links = definition_link_field.reverse_links;

            createNodeForCurrentArtifact(modal_model.graph, definition);

            createNodesAndLinksForOutgoingLinks(modal_model.graph, definition, outgoing_execution_links);
            createNodesAndLinksForIncomingLinks(modal_model.graph, definition, incoming_execution_links);

            createNodesAndLinksForOutgoingLinks(modal_model.graph, definition, outgoing_definition_links);
            createNodesAndLinksForIncomingLinks(modal_model.graph, definition, incoming_definition_links);

            modal_model.graph.nodes = _.uniq(modal_model.graph.nodes, 'id');
        }
        return modal_model;
    }

    function getArtifactLinksField(artifact) {
        return _.find(artifact.values, { type: 'art_link' });
    }

    function createNodeForCurrentArtifact(graph, artifact) {
        var me = { id: artifact.id, label: '#' + artifact.id };

        graph.nodes.push(me);
    }

    function createNodesAndLinksForOutgoingLinks(graph, artifact, outgoing_links) {
        _(outgoing_links).forEach(function (outgoing_link) {
            if (outgoing_link.id !== artifact.id &&
                outgoing_link.tracker.id !== SharedPropertiesService.getTrackerExecutionId()) {

                var link = {source: artifact.id, target: outgoing_link.id, type: 'arrow'},
                    node = {id: outgoing_link.id, label: '#' + outgoing_link.id};

                graph.links.push(link);
                graph.nodes.push(node);
            }
        });
    }

    function createNodesAndLinksForIncomingLinks(graph, artifact, incoming_links) {
        _(incoming_links).forEach(function(incoming_link) {
            if (incoming_link.id !== artifact.id &&
                incoming_link.tracker.id !== SharedPropertiesService.getTrackerExecutionId()) {

                var link = {source: incoming_link.id, target: artifact.id, type: 'arrow'},
                    node = {id: incoming_link.id, label: '#' + incoming_link.id};

                graph.links.push(link);
                graph.nodes.push(node);
            }
        });
    }
}

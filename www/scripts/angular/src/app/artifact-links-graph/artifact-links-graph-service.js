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
    'ArtifactLinksGraphRestService'
];

function ArtifactLinksGraphService(
    $modal,
    gettextCatalog,
    ArtifactLinksGraphModalLoading,
    ArtifactLinksGraphRestService
) {
    var self = this;

    _.extend(self, {
        showGraph        : showGraph,
        getGraphStructure: getGraphStructure
    });

    function showGraph(artifact_id) {
        ArtifactLinksGraphModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'artifact-links-graph/artifact-links-graph.tpl.html',
            controller : 'ArtifactLinksGraphCtrl as modal',
            resolve: {
                modal_model: function () {
                    return ArtifactLinksGraphRestService.getArtifact(artifact_id).then(function(response) {
                        return self.getGraphStructure(response);
                    });
                }
            }
        });
    }

    function getGraphStructure(artifact) {
        var modal_model = {
            errors: [],
            graph : {
                links: [],
                nodes: []
            }
        };

        var artifact_link_field = getArtifactLinksField(artifact);

        if (! artifact_link_field) {
            modal_model.errors.push(gettextCatalog.getString('Artifact links field not found.'));

        } else {
            var outgoing_links = artifact_link_field.links,
                incoming_links = artifact_link_field.reverse_links;

            createNodeForCurrentArtifact(modal_model.graph, artifact);
            createNodesAndLinksForOutgoingLinks(modal_model.graph, artifact, outgoing_links);
            createNodesAndLinksForIncomingLinks(modal_model.graph, artifact, incoming_links);

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
        _(outgoing_links).forEach(function(outgoing_link) {
            var link = { source: artifact.id, target: outgoing_link.id, type: 'arrow' },
                node = { id: outgoing_link.id, label: '#' + outgoing_link.id };

            graph.links.push(link);
            graph.nodes.push(node);
        });
    }

    function createNodesAndLinksForIncomingLinks(graph, artifact, incoming_links) {
        _(incoming_links).forEach(function(incoming_link) {
            var link = { source: incoming_link.id, target: artifact.id, type: 'arrow' },
                node = { id: incoming_link.id, label: '#' + incoming_link.id };

            graph.links.push(link);
            graph.nodes.push(node);
        });
    }
}

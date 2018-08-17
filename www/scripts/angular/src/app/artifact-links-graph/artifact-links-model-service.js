import _ from "lodash";

export default ArtifactLinksModelService;

function ArtifactLinksModelService() {
    var self = this;

    _.extend(self, {
        getGraphStructure: getGraphStructure
    });

    function getGraphStructure(artifact) {
        var modal_model = {
            errors: [],
            graph: {
                links: [],
                nodes: []
            },
            title: artifact.title
        };

        if (artifact.hasOwnProperty("error")) {
            modal_model.errors.push(artifact.error.message);
        } else {
            var outgoing_artifact_links = artifact.links,
                incoming_artifact_links = artifact.reverse_links;

            createNodeForCurrentArtifact(modal_model.graph, artifact);
            createNodesAndLinksForOutgoingLinks(modal_model, artifact, outgoing_artifact_links);
            createNodesAndLinksForIncomingLinks(modal_model, artifact, incoming_artifact_links);

            modal_model.graph.nodes = _.uniq(modal_model.graph.nodes, "id");
        }

        return modal_model;
    }

    function createNodeForCurrentArtifact(graph, artifact) {
        var current_node = artifact;
        delete current_node.links;
        delete current_node.reverse_links;
        graph.nodes.push(current_node);
    }

    function createNodesAndLinksForOutgoingLinks(model, artifact, outgoing_links) {
        _(outgoing_links).forEach(function(outgoing_link) {
            var link = {
                source: artifact.id,
                target: outgoing_link.id,
                type: "arrow"
            };

            model.graph.links.push(link);
            model.graph.nodes.push(outgoing_link);

            if (artifact.ref_name === "test_exec" && outgoing_link.ref_name === "test_def") {
                model.title = outgoing_link.title;
            }
        });
    }

    function createNodesAndLinksForIncomingLinks(model, artifact, incoming_links) {
        _(incoming_links).forEach(function(incoming_link) {
            var link = {
                source: incoming_link.id,
                target: artifact.id,
                type: "arrow"
            };

            model.graph.links.push(link);
            model.graph.nodes.push(incoming_link);
        });
    }
}

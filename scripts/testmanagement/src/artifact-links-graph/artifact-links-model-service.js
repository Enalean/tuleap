/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import _ from "lodash";

export default ArtifactLinksModelService;

function ArtifactLinksModelService() {
    var self = this;

    _.extend(self, {
        getGraphStructure: getGraphStructure,
    });

    function getGraphStructure(artifact) {
        var modal_model = {
            errors: [],
            graph: {
                links: [],
                nodes: [],
            },
            title: artifact.title,
        };

        if (Object.prototype.hasOwnProperty.call(artifact, "error")) {
            modal_model.errors.push(artifact.error.message);
        } else {
            var outgoing_artifact_links = artifact.links,
                incoming_artifact_links = artifact.reverse_links;

            createNodeForCurrentArtifact(modal_model.graph, artifact);
            createNodesAndLinksForOutgoingLinks(modal_model, artifact, outgoing_artifact_links);
            createNodesAndLinksForIncomingLinks(modal_model, artifact, incoming_artifact_links);

            //eslint-disable-next-line you-dont-need-lodash-underscore/uniq
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
        _(outgoing_links).forEach(function (outgoing_link) {
            var link = {
                source: artifact.id,
                target: outgoing_link.id,
                type: "arrow",
            };

            model.graph.links.push(link);
            model.graph.nodes.push(outgoing_link);

            if (artifact.ref_name === "test_exec" && outgoing_link.ref_name === "test_def") {
                model.title = outgoing_link.title;
            }
        });
    }

    function createNodesAndLinksForIncomingLinks(model, artifact, incoming_links) {
        _(incoming_links).forEach(function (incoming_link) {
            var link = {
                source: incoming_link.id,
                target: artifact.id,
                type: "arrow",
            };

            model.graph.links.push(link);
            model.graph.nodes.push(incoming_link);
        });
    }
}

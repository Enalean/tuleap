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

import angular from "angular";
import _, { has, remove } from "lodash-es";
import { zoom as d3_zoom, zoomIdentity } from "d3-zoom";
import { drag } from "d3-drag";
import { forceCenter, forceLink, forceManyBody, forceSimulation } from "d3-force";
import { select, selectAll } from "d3-selection";

export default Graph;

Graph.$inject = [
    "$window",
    "ArtifactLinksGraphRestService",
    "ArtifactLinksModelService",
    "ArtifactLinksArtifactsList",
    "$q",
    "$timeout",
];

function Graph(
    $window,
    ArtifactLinksGraphRestService,
    ArtifactLinksModelService,
    ArtifactLinksArtifactsList,
    $q,
    $timeout,
) {
    return {
        restrict: "E",
        scope: {
            model: "=",
        },
        link: function (scope, element) {
            var complements_graph = {};
            var nodes_duplicate = {};

            var loader,
                width,
                height,
                zoom,
                text,
                circle,
                path,
                figures,
                nodes,
                links,
                graph,
                svg,
                g,
                g_clickable,
                rect;

            function graphd3() {
                graphd3.init();

                function resize() {
                    var width = element.width(),
                        height = element.height();

                    graphd3.resize(height, width);
                    graphd3.reset();
                }

                angular.element($window).bind("resize", function () {
                    resize();
                });

                return graphd3;
            }

            graphd3.init = function () {
                var links = scope.model.links;
                var data_nodes = scope.model.nodes;

                graphd3.initLoader();
                graphd3.initSvg();
                graphd3.initData(links, data_nodes);
                graphd3.initLayout();

                graphd3.graph().nodes(Object.values(graphd3.nodes()));

                graphd3.graph().force("link").links(graphd3.links());

                graphd3.initGraph();
                graphd3.initEvent();
            };

            graphd3.initData = function (links, data_nodes) {
                var figures = getAllFigures(links);

                var nodes = {};

                links.forEach(function (link) {
                    link.source =
                        nodes[link.source] || (nodes[link.source] = findNode(link.source));
                    link.target =
                        nodes[link.target] || (nodes[link.target] = findNode(link.target));
                });

                graphd3.links(links);
                graphd3.nodes(nodes);
                graphd3.figures(figures);

                function findNode(node_id) {
                    return data_nodes.find((node) => node.id === node_id);
                }

                function getAllFigures(links) {
                    var data = [];
                    links.forEach(function (link) {
                        if (data.indexOf(link.type) === -1) {
                            data.push(link.type);
                        }
                    });
                    return data;
                }
            };

            graphd3.initGraph = function () {
                graphd3.initPath();
                graphd3.initCircle();
                graphd3.initText();
            };

            graphd3.initLayout = function () {
                graphd3.graph(
                    forceSimulation()
                        .velocityDecay(0.7)
                        .force(
                            "link",
                            forceLink()
                                .id(function (d) {
                                    return d.index;
                                })
                                .distance(100),
                        )
                        .force("charge", forceManyBody().strength(-150))
                        .force("center", forceCenter(graphd3.width() / 2, graphd3.height() / 2)),
                );

                graphd3.graph().on("tick", display);

                function display() {
                    graphd3.circle().attr("transform", function (d) {
                        return "translate(" + d.x + "," + d.y + ")";
                    });

                    graphd3.path().attr("d", function (d) {
                        var radius = 6,
                            arrow_length = 10,
                            theta = Math.atan2(d.target.y - d.source.y, d.target.x - d.source.x),
                            half_PI = Math.PI / 2,
                            first_x_point = d.target.x - radius * Math.cos(theta),
                            first_y_point = d.target.y - radius * Math.sin(theta),
                            first_dx_point =
                                radius * Math.cos(half_PI - theta) - arrow_length * Math.cos(theta),
                            first_dy_point =
                                -radius * Math.sin(half_PI - theta) -
                                arrow_length * Math.sin(theta),
                            second_x_point =
                                first_x_point -
                                radius * Math.cos(half_PI - theta) -
                                arrow_length * Math.cos(theta),
                            second_y_point =
                                first_y_point +
                                radius * Math.sin(half_PI - theta) -
                                arrow_length * Math.sin(theta);
                        return (
                            "M" +
                            d.source.x +
                            "," +
                            d.source.y +
                            "L" +
                            d.target.x +
                            "," +
                            d.target.y +
                            "M" +
                            first_x_point +
                            "," +
                            first_y_point +
                            "l" +
                            first_dx_point +
                            "," +
                            first_dy_point +
                            "L" +
                            second_x_point +
                            "," +
                            second_y_point +
                            "z"
                        );
                    });

                    selectAll(".graph-label").attr("transform", function (d) {
                        return "translate(" + d.x + "," + d.y + ")";
                    });
                    graphd3.g().style("display", "initial");
                    graphd3.gClickable().style("display", "initial");
                }
            };

            graphd3.initPath = function () {
                graphd3.path(
                    graphd3
                        .g()
                        .append("g")
                        .attr("class", "updatable")
                        .selectAll("path")
                        .data(graphd3.graph().force("link").links(), function (d) {
                            return d.source.id + "-" + d.target.id;
                        })
                        .enter()
                        .append("path")
                        .attr("class", function (d) {
                            if (d.id) {
                                return (
                                    "link " + d.type + " link_" + d.source.id + "_" + d.target.id
                                );
                            }
                            return "link " + d.type;
                        }),
                );
            };

            graphd3.initCircle = function () {
                graphd3.circle(
                    graphd3
                        .gClickable()
                        .append("g")
                        .attr("class", "updatable")
                        .selectAll("circle")
                        .data(graphd3.graph().nodes(), function (d) {
                            return d.id;
                        })
                        .enter()
                        .append("circle")
                        .attr("class", (d) =>
                            d.id
                                ? normalizeColor(d.color) + " circle_" + d.id
                                : normalizeColor(d.color),
                        )
                        .attr("r", 8)
                        .call(
                            drag()
                                .on("start", function (event) {
                                    if (!event.active) {
                                        graphd3.graph().alphaTarget(0.3).restart();
                                    }
                                })
                                .on("drag", function (event, d) {
                                    var position = calculatePosition(
                                        document.getElementsByClassName("graph-container")[0],
                                        document.getElementsByClassName(
                                            "graph-elements-clickable",
                                        )[0],
                                        event.x,
                                        event.y,
                                        graphd3.width(),
                                        graphd3.height(),
                                    );
                                    d.fx = position.x;
                                    d.fy = position.y;
                                })
                                .on("end", function (event) {
                                    if (!event.active) {
                                        graphd3.graph().alphaTarget(0);
                                    }
                                }),
                        )
                        .on("click", function (event, d) {
                            if (d.clicked && d.has_children) {
                                graphd3.remove(d);
                            } else {
                                select(".loader-node").style("visibility", "visible");
                                d.clicked = true;
                                d.has_children = false;
                                var complement = complements_graph[d.id];
                                if (complement) {
                                    d.has_children = true;
                                    graphd3.updateDataReady(complement);
                                } else {
                                    showGraph(d.id).then(function (result) {
                                        if (result.errors.length === 0) {
                                            d.has_children = true;
                                            graphd3.update(result.graph, d);
                                        } else {
                                            select(".loader-node").style("visibility", "hidden");
                                        }
                                    });
                                }
                            }
                        }),
                );
            };

            graphd3.initText = function () {
                graphd3.text(
                    graphd3
                        .gClickable()
                        .append("g")
                        .attr("class", "updatable")
                        .selectAll("a")
                        .data(graphd3.graph().nodes(), function (d) {
                            return d.id;
                        })
                        .enter()
                        .append("a")
                        .attr("class", function (d) {
                            if (d.id) {
                                return "graph-label updatable text_" + d.id;
                            }
                        })
                        .attr("xlink:href", function (d) {
                            return d.url;
                        })
                        .append("text")
                        .attr("class", "ref-name ")
                        .attr("x", 13)
                        .attr("y", ".31em")
                        .attr("id", function (d) {
                            return d.ref_name + "_" + d.id;
                        })
                        .text(function (d) {
                            return d.ref_name + " " + d.id;
                        }),
                );

                selectAll(".graph-label")
                    .insert("rect", ":first-child")
                    .attr("width", function (d) {
                        return (
                            angular.element("#" + d.ref_name + "_" + d.id)[0].getBBox().width + 6
                        );
                    })
                    .attr("height", function (d) {
                        return (
                            angular.element("#" + d.ref_name + "_" + d.id)[0].getBBox().height + 4
                        );
                    })
                    .attr("class", function (d) {
                        return "ref-name-background " + d.color.replace("_", "-");
                    })
                    .attr("height", function (d) {
                        return (
                            angular.element("#" + d.ref_name + "_" + d.id)[0].getBBox().height + 4
                        );
                    })
                    .attr("class", function (d) {
                        return "ref-name-background " + d.color.replace("_", "-");
                    })
                    .attr("x", 10)
                    .attr("y", -9)
                    .attr("rx", 3)
                    .attr("ry", 3);

                selectAll(".graph-label")
                    .append("text")
                    .attr("x", 10)
                    .attr("y", 20)
                    .text(function (d) {
                        return d.title + " ";
                    });
            };

            graphd3.initSvg = function () {
                graphd3.zoom(
                    d3_zoom()
                        .scaleExtent([0.5, 8])
                        .on("zoom", function (event) {
                            graphd3.g().attr("transform", event.transform);
                            graphd3.gClickable().attr("transform", event.transform);
                        }),
                );

                graphd3.width(element.width());
                graphd3.height(element.height());

                graphd3.svg(
                    select(element[0])
                        .append("svg")
                        .attr("class", "graph-container")
                        .attr("width", graphd3.width())
                        .attr("height", graphd3.height()),
                );

                graphd3.g(graphd3.svg().append("g").attr("class", "graph-elements"));

                graphd3.rect(
                    graphd3
                        .svg()
                        .append("rect")
                        .attr("class", "overlay")
                        .attr("width", graphd3.width())
                        .attr("height", graphd3.height())
                        .call(graphd3.zoom())
                        .on("wheel.zoom", null)
                        .on("dblclick.zoom", null),
                );

                graphd3.gClickable(
                    graphd3.svg().append("g").attr("class", "graph-elements-clickable"),
                );
            };

            graphd3.initEvent = function () {
                select("#focus-graph").on("click", function () {
                    graphd3.reset();
                });
                select("#zoomin-graph").on("click", function () {
                    graphd3.rect().transition().call(graphd3.zoom().scaleBy, 1.2);
                });
                select("#zoomout-graph").on("click", function () {
                    graphd3.rect().transition().call(graphd3.zoom().scaleBy, 0.8);
                });
            };

            graphd3.initLoader = function () {
                graphd3.loader(
                    select(".graph")
                        .append("img")
                        .attr("src", "/themes/BurningParrot/images/spinner.gif")
                        .attr("class", "loader loader-node"),
                );
            };

            graphd3.redraw = function () {
                graphd3.graph().nodes(graphd3.graph().nodes());

                graphd3.graph().force("link").links(graphd3.graph().force("link").links());

                graphd3.graph().alpha(0.9).restart();
            };

            graphd3.resize = function (height, width) {
                graphd3.height(height);
                graphd3.width(width);

                graphd3.svg().attr("width", graphd3.width()).attr("height", graphd3.height());
                graphd3
                    .graph()
                    .force("center")
                    .x(graphd3.width() / 2)
                    .y(graphd3.height() / 2);
            };

            graphd3.reset = function () {
                graphd3.resetNodes();
                graphd3.resetZoom();
                graphd3.graph().alpha(0.9).restart();
            };

            graphd3.resetNodes = function () {
                graphd3
                    .circle()
                    .data()
                    .forEach(function (d) {
                        d.fx = null;
                        d.fy = null;
                    });
            };

            graphd3.resetZoom = function () {
                graphd3.rect().transition().call(graphd3.zoom().transform, zoomIdentity);
            };

            graphd3.update = function (graph, node_event) {
                var complement = {
                    nodes: [],
                    links: [],
                };

                graph.nodes.forEach(function (node) {
                    const node_exist = graphd3
                        .graph()
                        .nodes()
                        .some((d3_node) => d3_node.id === node.id);
                    if (!node_exist) {
                        graphd3.nodes()[node.id] = node;
                        graphd3.graph().nodes().push(node);
                    }
                });

                graph.links.forEach(function (link) {
                    const link_exist = graphd3
                        .graph()
                        .force("link")
                        .links()
                        .some(
                            (d3_link) =>
                                d3_link.source.id === link.source &&
                                d3_link.target.id === link.target,
                        );

                    var d3_link = {
                        source: graphd3.nodes()[link.source],
                        target: graphd3.nodes()[link.target],
                        type: link.type,
                        id: graphd3.nodes()[link.source].id + "_" + graphd3.nodes()[link.target].id,
                    };

                    if (!link_exist) {
                        graphd3.graph().force("link").links().push(d3_link);
                        complement.links.push(d3_link);

                        var link_node;
                        if (node_event.id === d3_link.source.id) {
                            link_node = d3_link.target;
                        } else {
                            link_node = d3_link.source;
                        }

                        if (nodes_duplicate[link_node.id]) {
                            nodes_duplicate[link_node.id].in_graph_counter++;

                            if (nodes_duplicate[link_node.id].in_graph_counter === 1) {
                                nodes_duplicate[link_node.id].node = link_node;
                            }
                        } else {
                            nodes_duplicate[link_node.id] = {
                                in_graph_counter: 1,
                                node: link_node,
                            };
                        }
                        complement.nodes.push(nodes_duplicate[link_node.id].node);
                    }
                });

                complements_graph[node_event.id] = complement;

                select(".graph-elements").selectAll(".updatable").remove();
                select(".graph-elements-clickable").selectAll(".updatable").remove();
                graphd3.initGraph();

                select(".loader-node").style("visibility", "hidden");
                graphd3.redraw();
            };

            /**
             * Function to update graph with existing nodes
             *
             * If node is in nodes_duplicate we redirect the link
             * on this node
             */
            graphd3.updateDataReady = function (complement) {
                _(complement.nodes)
                    .compact()
                    .forEach(function (node) {
                        nodes_duplicate[node.id].in_graph_counter++;
                    });
                _(complement.links)
                    .compact()
                    .forEach(function (link) {
                        const node_source_exist = graphd3
                            .graph()
                            .nodes()
                            .some((d3_node) => d3_node.id === link.source.id);

                        if (nodes_duplicate[link.source.id]) {
                            link.source = nodes_duplicate[link.source.id].node;
                        }

                        if (!node_source_exist) {
                            graphd3.nodes()[link.source.id] = link.source;
                            graphd3.graph().nodes().push(link.source);
                        }

                        const node_target_exist = graphd3
                            .graph()
                            .nodes()
                            .some((d3_node) => d3_node.id === link.target.id);

                        if (nodes_duplicate[link.target.id]) {
                            link.target = nodes_duplicate[link.target.id].node;
                        }

                        if (!node_target_exist) {
                            graphd3.nodes()[link.target.id] = link.target;
                            graphd3.graph().nodes().push(link.target);
                        }

                        graphd3.graph().force("link").links().push(link);
                    });
                select(".graph-elements").selectAll(".updatable").remove();
                select(".graph-elements-clickable").selectAll(".updatable").remove();
                graphd3.initGraph();

                select(".loader-node").style("visibility", "hidden");
                graphd3.redraw();
            };

            graphd3.remove = function (node_event) {
                graphd3.nodeRemove(node_event);

                delete node_event.clicked;
                delete node_event.has_children;
                graphd3.redraw();
            };

            graphd3.nodeRemove = function (node_event) {
                var neighbors;
                var view;
                var node;
                var is_there_an_operation = true;

                removeLinks(node_event);

                // Decrement counter
                while (is_there_an_operation) {
                    view = [];
                    neighbors = [];
                    neighbors.push(node_event);
                    view[node_event.id] = true;
                    is_there_an_operation = false;

                    while (neighbors.length > 0) {
                        node = neighbors.shift();
                        removeLinks(node);
                        addNeighborsNode(node);
                        if (
                            nodes_duplicate[node.id] &&
                            nodes_duplicate[node.id].in_graph_counter > 0
                        ) {
                            if (artifactMustBeRemoved(node)) {
                                nodes_duplicate[node.id].in_graph_counter--;
                                is_there_an_operation = true;
                            }
                        }
                    }
                }

                view = [];
                neighbors = [];
                neighbors.push(node_event);
                view[node_event.id] = true;

                // Remove nodes with counter equal to 0
                while (neighbors.length > 0) {
                    node = neighbors.shift();
                    addNeighborsNode(node);

                    if (
                        nodes_duplicate[node.id] &&
                        nodes_duplicate[node.id].in_graph_counter === 0
                    ) {
                        removeNode(node);
                        removeLinks(node);

                        delete node.clicked;
                        delete node.has_children;
                    }
                }

                function removeNode(node) {
                    remove(graphd3.graph().nodes(), function (d3_node) {
                        if (d3_node.id === node.id) {
                            selectAll(".circle_" + node.id).remove();
                            selectAll(".text_" + node.id).remove();
                            return true;
                        }
                        return false;
                    });
                }

                function addNeighborsNode(node) {
                    var complement = complements_graph[node.id];
                    if (complement) {
                        _(complement.nodes)
                            .compact()
                            .forEach(function (d3_node) {
                                if (!view[d3_node.id]) {
                                    neighbors.push(d3_node);
                                    view[d3_node.id] = true;
                                }
                            });
                    }
                }

                function removeLinks(node) {
                    var complement = complements_graph[node.id];
                    if (complement) {
                        _(complement.links)
                            .compact()
                            .forEach(function (link) {
                                remove(graphd3.graph().force("link").links(), function (d3_link) {
                                    if (
                                        d3_link.source.id === link.source.id &&
                                        d3_link.target.id === link.target.id
                                    ) {
                                        selectAll(".link_" + link.id).remove();
                                        return true;
                                    }
                                    return false;
                                });
                            });
                    }
                }

                function artifactMustBeRemoved(node) {
                    const link_exist = graphd3
                        .graph()
                        .force("link")
                        .links()
                        .some((link) => link.source.id === node.id || link.target.id === node.id);
                    return !link_exist;
                }
            };

            graphd3.svg = function (new_svg) {
                if (!arguments.length) {
                    return svg;
                }
                svg = new_svg;
                return graphd3;
            };

            graphd3.g = function (new_g) {
                if (!arguments.length) {
                    return g;
                }
                g = new_g;
                return graphd3;
            };

            graphd3.gClickable = function (new_g_clickable) {
                if (!arguments.length) {
                    return g_clickable;
                }
                g_clickable = new_g_clickable;
                return graphd3;
            };

            graphd3.rect = function (new_rect) {
                if (!arguments.length) {
                    return rect;
                }
                rect = new_rect;
                return graphd3;
            };

            graphd3.graph = function (newGraph) {
                if (!arguments.length) {
                    return graph;
                }
                graph = newGraph;
                return graphd3;
            };

            graphd3.links = function (newLinks) {
                if (!arguments.length) {
                    return links;
                }
                links = newLinks;
                return graphd3;
            };

            graphd3.nodes = function (newNodes) {
                if (!arguments.length) {
                    return nodes;
                }
                nodes = newNodes;
                return graphd3;
            };

            graphd3.figures = function (newFigures) {
                if (!arguments.length) {
                    return figures;
                }
                figures = newFigures;
                return graphd3;
            };

            graphd3.path = function (newPath) {
                if (!arguments.length) {
                    return path;
                }
                path = newPath;
                return graphd3;
            };

            graphd3.circle = function (newCircle) {
                if (!arguments.length) {
                    return circle;
                }
                circle = newCircle;
                return graphd3;
            };

            graphd3.text = function (newText) {
                if (!arguments.length) {
                    return text;
                }
                text = newText;
                return graphd3;
            };

            graphd3.width = function (newWidth) {
                if (!arguments.length) {
                    return width;
                }
                width = newWidth;
                return graphd3;
            };

            graphd3.height = function (newHeight) {
                if (!arguments.length) {
                    return height;
                }
                height = newHeight;
                return graphd3;
            };

            graphd3.zoom = function (newZoom) {
                if (!arguments.length) {
                    return zoom;
                }
                zoom = newZoom;
                return graphd3;
            };

            graphd3.loader = function (newloader) {
                if (!arguments.length) {
                    return loader;
                }
                loader = newloader;
                return graphd3;
            };

            $timeout(graphd3, 0);
        },
    };

    function showGraph(artifact_id) {
        if (!artifactExist(artifact_id)) {
            return ArtifactLinksGraphRestService.getArtifactGraph(artifact_id).then(
                function (artifact) {
                    ArtifactLinksArtifactsList.artifacts[artifact_id] =
                        ArtifactLinksModelService.getGraphStructure(artifact);
                    return ArtifactLinksArtifactsList.artifacts[artifact_id];
                },
            );
        }
        return $q(function (resolve) {
            return resolve(ArtifactLinksArtifactsList.artifacts[artifact_id]);
        });
    }

    function artifactExist(artifact_id) {
        return has(ArtifactLinksArtifactsList.artifacts, artifact_id);
    }

    function calculatePosition(svg, element, x, y, width, height) {
        var relative_point = { x: x, y: y },
            new_x = x,
            new_y = y,
            transformed_point,
            is_x_changed = false,
            is_y_changed = false;

        if (svg && element) {
            relative_point = getRelativeXY(svg, element, x, y);
        }

        if (relative_point.x < 10) {
            new_x = 10;
            is_x_changed = true;
        } else if (relative_point.x > width - 10) {
            new_x = width - 10;
            is_x_changed = true;
        }

        if (relative_point.y < 10) {
            new_y = 10;
            is_y_changed = true;
        } else if (relative_point.y > height - 10) {
            new_y = height - 10;
            is_y_changed = true;
        }

        if (is_x_changed) {
            transformed_point = getTransformedX(svg, element, new_x);
            new_x = transformed_point.x;
        }

        if (is_y_changed) {
            transformed_point = getTransformedY(svg, element, new_y);
            new_y = transformed_point.y;
        }

        return { x: new_x, y: new_y };
    }

    function getRelativeXY(svg, element, x, y) {
        var point = svg.createSVGPoint(),
            element_coordinate_system = element.getCTM();

        point.x = x;
        point.y = y;

        return point.matrixTransform(element_coordinate_system);
    }

    function getTransformedX(svg, element, x) {
        var point = svg.createSVGPoint(),
            element_coordinate_system_inverse = element.getCTM().inverse();

        point.x = x;

        return point.matrixTransform(element_coordinate_system_inverse);
    }

    function getTransformedY(svg, element, y) {
        var point = svg.createSVGPoint(),
            element_coordinate_system_inverse = element.getCTM().inverse();

        point.y = y;

        return point.matrixTransform(element_coordinate_system_inverse);
    }

    function normalizeColor(color) {
        const all_underscores = /_/g;

        return color.replace(all_underscores, "-");
    }
}

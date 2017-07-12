import angular from 'angular';
import _ from 'lodash';
import * as d3 from 'd3';

export default Graph;

Graph.$inject = [
    '$window',
    'ArtifactLinksGraphRestService',
    'ArtifactLinksModelService',
    'ArtifactLinksArtifactsList',
    '$q'
];

function Graph(
    $window,
    ArtifactLinksGraphRestService,
    ArtifactLinksModelService,
    ArtifactLinksArtifactsList,
    $q
) {
    return {
        restrict: 'E',
        scope: {
            model: '='
        },
        link: function (scope, element) {
            var complements_graph = {};
            var nodes_duplicate = {};

            function graphd3() {
                graphd3.init();

                function resize() {
                    var width = element.width(),
                        height = element.height();

                    graphd3.resize(height, width);

                    graphd3.redraw();
                }

                angular.element($window).bind('resize', function(event) {
                    resize();
                });

                return graphd3;
            }

            graphd3.init = function () {
                var links = scope.model.links;
                var data_nodes = scope.model.nodes;

                graphd3.initLoader();
                graphd3.initSvg();
                graphd3.initRect();
                graphd3.initEvent();
                graphd3.initData(links, data_nodes);
                graphd3.initLayout();
                graphd3.initGraph();
            };

            graphd3.initData = function (links, data_nodes) {
                var figures = getAllFigures(links);

                var nodes = {};

                links.forEach(function(link) {
                    link.source = nodes[link.source] || (nodes[link.source] = findNode(link.source));
                    link.target = nodes[link.target] || (nodes[link.target] = findNode(link.target));
                });

                graphd3.links(links);
                graphd3.nodes(nodes);
                graphd3.figures(figures);

                function findNode(node_id) {
                    var node = _.find(data_nodes, function(node) {
                        return node.id === node_id;
                    });
                    return node;
                }

                function getAllFigures(links) {
                    var data = [];
                    links.forEach(function(link) {
                        if(data.indexOf(link.type) === -1) {
                            data.push(link.type);
                        }
                    });
                    return data;
                }
            };

            graphd3.initGraph = function () {
                graphd3.initMarkers();
                graphd3.initPath();
                graphd3.initCircle();
                graphd3.initText();
            };

            graphd3.initLayout = function() {
                graphd3.graph(d3.layout.force()
                    .nodes(d3.values(graphd3.nodes()))
                    .links(graphd3.links())
                    .size([graphd3.width(), graphd3.height()])
                    .linkDistance(100)
                    .charge(-1000)
                    .on("tick", display)
                    .start());

                function display() {
                    graphd3.path().attr("d", linkArc);
                    graphd3.circle().attr("transform", transform);
                    d3.selectAll(".graph-label").attr("transform", transform);
                    graphd3.svg().style("display", "initial");
                }

                function linkArc(d) {
                    var dx = d.target.x - d.source.x,
                        dy = d.target.y - d.source.y,
                        dr = Math.sqrt(dx * dx + dy * dy);
                    return "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
                }

                function transform(d) {
                    return "translate(" + d.x + "," + d.y + ")";
                }
            };

            graphd3.initMarkers = function() {
                // Define the form of markers
                graphd3.svg().append("defs")
                    .attr("class", "updatable")
                    .selectAll("marker")
                    .data(graphd3.figures())
                    .enter().append("marker")
                    .attr("id", function(d) { return d; })
                    .attr("viewBox", "0 -5 10 10")
                    .attr("refX", 15)
                    .attr("refY", -1.5)
                    .attr("markerWidth", 6)
                    .attr("markerHeight", 6)
                    .attr("orient", "auto")
                    .append("path")
                    .attr("d", "M0,-5L10,0L0,5");
            };

            graphd3.initPath = function() {
                graphd3.path(graphd3.svg().append("g")
                    .attr("class", "updatable")
                    .selectAll("path")
                    .data(graphd3.graph().links(), function(d){{ return d.source.id + "-" + d.target.id; }})
                    .enter().append("path")
                    .attr("class", function(d) {
                        if (d.id) {
                            return "link " + d.type + " " + d.source.id + "_" + d.target.id;
                        } else {
                            return "link " + d.type;
                        }
                    })
                    .attr("marker-end", function(d) { return "url(#" + d.type + ")"; }));
            };

            graphd3.initCircle = function() {
                graphd3.circle(graphd3.svg().append("g")
                        .attr("class", "updatable")
                        .selectAll("circle")
                        .data(graphd3.graph().nodes(), function(d) { return d.id; })
                        .enter().append("circle")
                        .attr("class", function(d) {
                            if (d.id) {
                                return d.color + " " + d.id;
                            } else {
                                return d.color;
                            }
                        })
                        .attr("r", 8)
                        .on('click', function(d) {
                            if (d.clicked && d.has_children) {
                                graphd3.remove(d);
                            } else {
                                d3.select(".loader-node").style("visibility", "visible");
                                d.clicked = true;
                                d.has_children = false;
                                var complement = complements_graph[d.id];
                                if (complement) {
                                    d.has_children = true;
                                    graphd3.updateDataReady(complement);
                                } else {
                                    showGraph(d.id).then(function(result) {
                                        if (result.errors.length === 0) {
                                            d.has_children = true;
                                            graphd3.update(result.graph, d);
                                        } else {
                                            d3.select(".loader-node").style("visibility", "hidden");
                                        }
                                    });
                                }
                            }
                        })
                );
            };

            graphd3.initText = function() {
                graphd3.text(graphd3.svg().append("g")
                        .attr("class", "updatable")
                        .selectAll("a")
                        .data(graphd3.graph().nodes(), function (d) {
                            return d.id;
                        })
                        .enter()
                        .append("a")
                        .attr("class", function (d) {
                            if (d.id) {
                                return "graph-label updatable " + d.id;
                            }
                        })
                        .attr("xlink:href", function(d) {
                            return d.url;
                        })
                        .append("text")
                        .attr("x", 13)
                        .attr("y", ".31em")
                        .attr("id", function (d) {
                            return d.ref_name + "_" + d.id;
                        })
                        .attr("class", "ref-name ")
                        .text(function (d) {
                            return d.ref_name + " #" + d.id;
                        })
                );

                d3.selectAll(".graph-label")
                    .insert("rect", ":first-child")
                    .attr("width", function(d) {
                        return angular.element("#" + d.ref_name + "_" + d.id)[0].getBBox().width + 6;
                    })
                    .attr("height", function(d) {
                        return angular.element("#" + d.ref_name + "_" + d.id)[0].getBBox().height + 4;
                    })
                    .attr("class", function(d) {
                        return "ref-name-background " + d.color;
                    })
                    .attr("x", 10)
                    .attr("y", -9)
                    .attr("rx", 3)
                    .attr('ry', 3);

                d3.selectAll(".graph-label")
                    .append("text")
                    .attr("x", 10)
                    .attr("y", 20)
                    .text(function (d) {
                        return d.title + " ";
                    });
            };

            graphd3.initSvg = function() {
                graphd3.scaleMin(0.5);
                graphd3.scaleMax(8);
                graphd3.zoom(d3.behavior.zoom().scaleExtent([graphd3.scaleMin(), graphd3.scaleMax()]).on("zoom", graphd3.transformZoom));

                d3.select("svg").remove();

                graphd3.width(element.width());
                graphd3.height(element.height());

                graphd3.svg(d3.select(element[0]).append("svg")
                        .attr("width", graphd3.width())
                        .attr("height", graphd3.height())
                        .append("g")
                        .call(graphd3.zoom())
                        .append("g")
                        .attr("class", "graph-elements")
                );
            };

            graphd3.initRect = function() {
                graphd3.svg().append("rect")
                    .attr("class", "overlay")
                    .attr("width", graphd3.width())
                    .attr("height", graphd3.height());
            };

            graphd3.initEvent = function() {
                d3.select("#focus-graph")
                    .on("click", function() {
                        graphd3.zoom().scale(1);
                        graphd3.zoom().translate([0,0]);
                        graphd3.svg().attr("transform", "translate(" + 0 + "," + 0 + ")scale(" + 1 + ")");
                    });
                d3.select("#zoomin-graph")
                    .on("click", function() {
                        graphd3.zoomInOut("zoomin");
                    });
                d3.select("#zoomout-graph")
                    .on("click", function() {
                        graphd3.zoomInOut("zoomout");
                    });
            };

            graphd3.initLoader = function() {
                graphd3.loader(d3.select(".graph").append("img")
                    .attr("src", "scripts/angular/bin/assets/loader.gif")
                    .attr("class", "loader")
                    .attr("class", "loader-node"));
            };

            graphd3.redraw = function() {
                d3.select(".overlay").attr("width", graphd3.width()).attr("height", graphd3.height());
                d3.select("svg").attr("width", graphd3.width()).attr("height", graphd3.height());
                graphd3.graph().size([graphd3.width(), graphd3.height()]).resume();
            };

            graphd3.update = function(graph, node_event) {
                var complement = {
                    nodes: [],
                    links: []
                };

                _(graph.nodes).forEach(function(node) {
                    var node_exist = _.find(graphd3.graph().nodes(), function(d3_node) {
                        return d3_node.id === node.id;
                    });
                    if (! node_exist) {
                        graphd3.nodes()[node.id] = node;
                        graphd3.graph().nodes().push(node);
                    }
                });
                _(graph.links).forEach(function(link) {
                    var link_exist = _.find(graphd3.graph().links(), function(d3_link) {
                        return d3_link.source.id === link.source && d3_link.target.id === link.target;
                    });

                    var d3_link = {
                        source  : graphd3.nodes()[link.source],
                        target  : graphd3.nodes()[link.target],
                        type    : link.type,
                        id      : graphd3.nodes()[link.source].id + "_" + graphd3.nodes()[link.target].id
                    };

                    if (! link_exist) {
                        graphd3.graph().links().push(d3_link);
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
                                node: link_node
                            };
                        }
                        complement.nodes.push(nodes_duplicate[link_node.id].node);
                    }
                });

                complements_graph[node_event.id] = complement;

                d3.select(".graph-elements").selectAll(".updatable").remove();
                graphd3.initGraph();

                d3.select(".loader-node").style("visibility", "hidden");
                graphd3.graph().start();
                graphd3.redraw();
            };

            /**
             * Function to update graph with existing nodes
             *
             * If node is in nodes_duplicate we redirect the link
             * on this node
             */
            graphd3.updateDataReady = function(complement) {
                _(complement.nodes).compact().forEach(function(node) {
                    nodes_duplicate[node.id].in_graph_counter++;
                });
                _(complement.links).compact().forEach(function(link) {
                    var node_source_exist = _.find(graphd3.graph().nodes(), function(d3_node) {
                        return d3_node.id === link.source.id;
                    });

                    if (nodes_duplicate[link.source.id]) {
                        link.source = nodes_duplicate[link.source.id].node;
                    }

                    if (! node_source_exist) {
                        graphd3.nodes()[link.source.id] = link.source;
                        graphd3.graph().nodes().push(link.source);
                    }

                    var node_target_exist = _.find(graphd3.graph().nodes(), function(d3_node) {
                        return d3_node.id === link.target.id;
                    });

                    if (nodes_duplicate[link.target.id]){
                        link.target = nodes_duplicate[link.target.id].node;
                    }

                    if (! node_target_exist) {
                        graphd3.nodes()[link.target.id] = link.target;
                        graphd3.graph().nodes().push(link.target);
                    }

                    graphd3.graph().links().push(link);
                });
                d3.select(".graph-elements").selectAll(".updatable").remove();
                graphd3.initGraph();

                d3.select(".loader-node").style("visibility", "hidden");
                graphd3.graph().start();
                graphd3.redraw();
            };

            graphd3.remove = function(node_event) {
                graphd3.nodeRemove(node_event);

                delete node_event['clicked'];
                delete node_event['has_children'];
                graphd3.graph().start();
                graphd3.redraw();
            };

            graphd3.nodeRemove = function(node_event) {
                var neighbors;
                var view;
                var node;
                var is_there_an_operation = true;

                removeLinks(node_event);

                // Decrement counter
                while (is_there_an_operation) {
                    view      = [];
                    neighbors = [];
                    neighbors.push(node_event);
                    view[node_event.id] = true;
                    is_there_an_operation = false;

                    while (neighbors.length > 0) {
                        node = neighbors.shift();
                        removeLinks(node);
                        addNeighborsNode(node);
                        if (nodes_duplicate[node.id] && nodes_duplicate[node.id].in_graph_counter > 0) {
                            if (artifactMustBeRemoved(node)) {
                                nodes_duplicate[node.id].in_graph_counter--;
                                is_there_an_operation = true;
                            }
                        }
                    }
                }

                view      = [];
                neighbors = [];
                neighbors.push(node_event);
                view[node_event.id] = true;

                // Remove nodes with counter equal to 0
                while (neighbors.length > 0) {
                    node = neighbors.shift();
                    addNeighborsNode(node);

                    if (node.id !== node_event.id && nodes_duplicate[node.id] && nodes_duplicate[node.id].in_graph_counter === 0) {
                        removeNode(node);
                        removeLinks(node);

                        delete node['clicked'];
                        delete node['has_children'];
                    }
                }

                function removeNode(node) {
                    _.remove(graphd3.graph().nodes(), function (d3_node) {
                        if (d3_node.id === node.id) {
                            d3.selectAll("." + node.id).remove();
                            return true;
                        } else {
                            return false;
                        }
                    });

                }

                function addNeighborsNode(node) {
                    var complement = complements_graph[node.id];
                    if (complement) {
                        _(complement.nodes).compact().forEach(function(d3_node) {
                            if (! view[d3_node.id]) {
                                neighbors.push(d3_node);
                                view[d3_node.id] = true;
                            }
                        });
                    }
                }

                function removeLinks(node) {
                    var complement = complements_graph[node.id];
                    if (complement) {
                        _(complement.links).compact().forEach(function (link) {
                            _.remove(graphd3.graph().links(), function (d3_link) {
                                if (d3_link.source.id === link.source.id && d3_link.target.id === link.target.id) {
                                    d3.selectAll("." + link.id).remove();
                                    return true;
                                } else {
                                    return false;
                                }
                            });
                        });
                    }
                }

                function artifactMustBeRemoved(node) {
                    var link_exist = _.find(graphd3.graph().links(), function(link) {
                        return link.source.id === node.id || link.target.id === node.id;
                    });

                    return ! link_exist;
                }
            };

            graphd3.resize = function (height, width) {
                if (arguments.length) {
                    graphd3.height(height);
                    graphd3.width(width);
                }
                return graphd3;
            };

            graphd3.transformZoom = function() {
                graphd3.svg().attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
            };

            graphd3.zoomInOut = function(zoom) {
                var scale = graphd3.zoom().scale(),
                    translate = graphd3.zoom().translate(),
                    x = translate[0], y = translate[1],
                    factor = 0.2,
                    target_scale = (zoom === 'zoomin') ?
                        scale + factor > graphd3.scaleMax() ?
                            graphd3.scaleMax() : scale + factor
                        : scale - factor < graphd3.scaleMin() ?
                        graphd3.scaleMin() : scale - factor;

                if (target_scale !== graphd3.scaleMin() && target_scale !== graphd3.scaleMax()) {
                    x = (zoom === 'zoomin') ? x - ((graphd3.width() / 2) * factor) : x + ((graphd3.width() / 2) * factor);
                    y = (zoom === 'zoomin') ? y - ((graphd3.height() / 2) * factor) : y + ((graphd3.height() / 2) * factor);

                    graphd3.zoom().scale(target_scale);
                    graphd3.zoom().translate([x, y]);
                    graphd3.svg().attr("transform", "translate(" + graphd3.zoom().translate()[0] + ", " + graphd3.zoom().translate()[1] + ")scale(" + graphd3.zoom().scale() + ")");
                }
            };

            graphd3.svg = function (newSvg) {
                if (!arguments.length) {
                    return svg;
                }
                svg = newSvg;
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

            graphd3.scaleMax = function (newScaleMax) {
                if (!arguments.length) {
                    return scaleMax;
                }
                scaleMax = newScaleMax;
                return graphd3;
            };

            graphd3.scaleMin = function (newScaleMin) {
                if (!arguments.length) {
                    return scaleMin;
                }
                scaleMin = newScaleMin;
                return graphd3;
            };

            graphd3.loader = function (newloader) {
                if (!arguments.length) {
                    return loader;
                }
                loader = newloader;
                return graphd3;
            };

            graphd3();
        }
    };

    function showGraph(artifact_id) {
        if (! artifactExist(artifact_id)) {
            return ArtifactLinksGraphRestService.getArtifactGraph(artifact_id).then(function(artifact) {
                ArtifactLinksArtifactsList.artifacts[artifact_id] = ArtifactLinksModelService.getGraphStructure(artifact);
                return ArtifactLinksArtifactsList.artifacts[artifact_id];
            });
        } else {
            return $q(function(resolve) {
                return resolve(ArtifactLinksArtifactsList.artifacts[artifact_id]);
            });
        }
    }

    function artifactExist(artifact_id) {
        return _.has(ArtifactLinksArtifactsList.artifacts, artifact_id);
    }
}


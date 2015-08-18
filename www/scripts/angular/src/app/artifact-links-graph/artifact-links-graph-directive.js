(function () {
    angular
        .module('tuleap.artifact-links-graph')
        .directive('graph', Graph)
        .value('ArtifactLinksArtifactsList', {
            artifacts: {}
        });

    Graph.$inject = [
        '$window',
        'ArtifactLinksGraphRestService',
        'ArtifactLinksModelService',
        'ArtifactLinksTrackerService',
        'ArtifactLinksArtifactsList'
    ];

    function Graph(
        $window,
        ArtifactLinksGraphRestService,
        ArtifactLinksModelService,
        ArtifactLinksTrackerService,
        ArtifactLinksArtifactsList
    ) {
        return {
            restrict: 'E',
            scope: {
                model: '='
            },
            link: function (scope, element) {
                var complements_graph = {};

                function graphd3() {
                    graphd3.init();

                    function resize() {
                        var width = element.width(),
                            height = element.height();

                        graphd3.resize(height, width);

                        graphd3.redraw();
                    }

                    angular.element($window).bind('resize', function() {
                        resize();
                    });

                    return graphd3;
                }

                graphd3.init = function () {
                    var links = scope.model.links;
                    var data_nodes = scope.model.nodes;

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
                        link.source = nodes[link.source] || (nodes[link.source] = {
                                name: findNode(link.source, "label"),
                                color_name: findNode(link.source, "color_name"),
                                id: findNode(link.source, "id")
                            });
                        link.target = nodes[link.target] || (nodes[link.target] = {
                                name: findNode(link.target, "label"),
                                color_name: findNode(link.target, "color_name"),
                                id: findNode(link.target, "id")
                            });
                    });

                    graphd3.links(links);
                    graphd3.nodes(nodes);
                    graphd3.figures(figures);

                    function findNode(node_id, data) {
                        var node = _.find(data_nodes, function(node) {
                            return node.id === node_id;
                        });
                        return node[data];
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
                        graphd3.text().attr("transform", transform);
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
                                    return d.color_name + " " + d.id;
                                } else {
                                    return d.color_name;
                                }
                            })
                            .attr("r", 8)
                            .on('click', function(d) {
                                if (d.clicked && d.has_children) {
                                    graphd3.remove(d);
                                } else {
                                    d3.select(".loader-graph").style("visibility", "visible");
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
                                                d3.select(".loader-graph").style("visibility", "hidden");
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
                        .selectAll("text")
                        .data(graphd3.graph().nodes(), function(d) { return d.id; })
                        .enter().append("text")
                        .attr("class", function(d) {
                            if (d.id) {
                                return "updatable " + d.id;
                            }
                        })
                        .attr("x", 10)
                        .attr("y", ".31em")
                        .text(function(d) { return d.name; }));
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

                graphd3.redraw = function() {
                    graphd3.svg().attr("width", graphd3.width()).attr("height", graphd3.height());
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
                            var d3_node = {
                                name        : node.label,
                                color_name  : node.color_name,
                                id          : node.id
                            };
                            graphd3.nodes()[node.id] = d3_node;
                            graphd3.graph().nodes().push(d3_node);
                            complement.nodes.push(d3_node);
                        }
                    });
                    _(graph.links).forEach(function(link) {
                        var link_exist = _.find(graphd3.graph().links(), function(d3_link) {
                            return d3_link.source.id === link.source && d3_link.target.id === link.target;
                        });

                        if (! link_exist) {
                            var d3_link = {
                                source  : graphd3.nodes()[link.source],
                                target  : graphd3.nodes()[link.target],
                                type    : link.type,
                                id      : graphd3.nodes()[link.source].id + "_" + graphd3.nodes()[link.target].id
                            };
                            graphd3.graph().links().push(d3_link);
                            complement.links.push(d3_link);
                        }
                    });
                    complements_graph[node_event.id] = complement;

                    d3.select(".graph-elements").selectAll(".updatable").remove();
                    graphd3.initGraph();

                    d3.select(".loader-graph").style("visibility", "hidden");
                    graphd3.graph().start();
                    graphd3.redraw();
                };

                graphd3.updateDataReady = function(complement) {
                    _(complement.nodes).compact().forEach(function(node) {
                        graphd3.graph().nodes().push(node);
                    });
                    _(complement.links).compact().forEach(function(link) {
                        graphd3.graph().links().push(link);
                    });
                    d3.select(".graph-elements").selectAll(".updatable").remove();
                    graphd3.initGraph();

                    d3.select(".loader-graph").style("visibility", "hidden");
                    graphd3.graph().start();
                };

                graphd3.remove = function(node_event) {
                    graphd3.nodeRemove(node_event);

                    delete node_event['clicked'];
                    delete node_event['has_children'];
                    graphd3.graph().start();
                    graphd3.redraw();
                };

                graphd3.nodeRemove = function(node_event) {
                    var complement = complements_graph[node_event.id];
                    if (complement) {
                        _(complement.nodes).compact().forEach(function(node) {
                            _.remove(graphd3.graph().nodes(), function(d3_node) {
                                if (d3_node.id === node.id) {
                                    d3.selectAll("." + node.id).remove();
                                    return true;
                                } else {
                                    return false;
                                }
                            });
                            _(complement.links).compact().forEach(function(link) {
                                _.remove(graphd3.graph().links(), function (d3_link) {
                                    if (d3_link.source.id === link.source.id && d3_link.target.id === link.target.id) {
                                        d3.selectAll("." + link.id).remove();
                                        return true;
                                    } else {
                                        return false;
                                    }
                                });
                            });
                            delete node['clicked'];
                            delete node['has_children'];

                            graphd3.nodeRemove(node);
                        });
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

                graphd3();
            }
        };

        function showGraph(artifact_id) {
            if (! artifactExist(artifact_id)) {
                return ArtifactLinksGraphRestService.getArtifact(artifact_id).then(function(result) {
                    var artifacts = constructArtifact(result);

                    ArtifactLinksArtifactsList.artifacts[artifact_id] = result;

                    return ArtifactLinksTrackerService.initializeTrackers(artifacts).then(function(trackers) {
                        return ArtifactLinksModelService.getGraphStructure(artifacts, trackers);
                    });
                });
            } else {
                var artifacts = constructArtifact(ArtifactLinksArtifactsList.artifacts[artifact_id]);

                return ArtifactLinksTrackerService.initializeTrackers(artifacts).then(function(trackers) {
                    return ArtifactLinksModelService.getGraphStructure(artifacts, trackers);
                });
            }
        }

        function artifactExist(artifact_id) {
            return _.has(ArtifactLinksArtifactsList.artifacts, artifact_id);
        }

        function constructArtifact(node) {
            return {
                nodes: [node],
                current_node: node
            };
        }
    }
})();
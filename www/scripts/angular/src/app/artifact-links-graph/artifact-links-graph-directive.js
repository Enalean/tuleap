angular
    .module('tuleap.artifact-links-graph')
    .directive('graph', Graph);

Graph.$inject = ['$window'];

function Graph($window) {
    return {
        restrict: 'E',
        scope: {
            model: '='
        },
        link: function (scope, element, attr) {
            function graphd3() {
                graphd3.init();

                function resize() {
                    var width = angular.element(element).width(),
                        height = angular.element(element).height();

                    graphd3.resize(height, width);

                    graphd3.redraw();
                }

                angular.element($window).bind('resize', function() {
                    resize();
                });

                return graphd3;
            }

            graphd3.init = function () {
                graphd3.initData();
                graphd3.initSvg();
                graphd3.initGraph();
            };

            graphd3.initData = function () {
                var links = scope.model.links;
                var data_nodes = scope.model.nodes;
                var figures = getAllFigures(links);

                var nodes = {};

                links.forEach(function(link) {
                    link.source = nodes[link.source] || (nodes[link.source] = {
                            name: findNode(link.source, "label"), color_name: findNode(link.source, "color_name")
                        });
                    link.target = nodes[link.target] || (nodes[link.target] = {
                            name: findNode(link.target, "label"), color_name: findNode(link.target, "color_name")
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
                graphd3.graph(d3.layout.force()
                    .nodes(d3.values(graphd3.nodes()))
                    .links(graphd3.links())
                    .size([graphd3.width(), graphd3.height()])
                    .linkDistance(100)
                    .charge(-1000)
                    .on("tick", tick)
                    .start());

                // Define the form of markers
                graphd3.svg().append("defs").selectAll("marker")
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

                var path = graphd3.svg().append("g").selectAll("path")
                    .data(graphd3.graph().links())
                    .enter().append("path")
                    .attr("class", function(d) { return "link " + d.type; })
                    .attr("marker-end", function(d) { return "url(#" + d.type + ")"; });

                var circle = graphd3.svg().append("g").selectAll("circle")
                    .data(graphd3.graph().nodes())
                    .enter().append("circle")
                    .attr("class", function(d) {
                        return d.color_name;
                    })
                    .attr("r", 8);

                var text = graphd3.svg().append("g").selectAll("text")
                    .data(graphd3.graph().nodes())
                    .enter().append("text")
                    .attr("x", 10)
                    .attr("y", ".31em")
                    .text(function(d) { return d.name; });

                // Use elliptical arc path segments to doubly-encode directionality.
                function tick() {
                    path.attr("d", linkArc);
                    circle.attr("transform", transform);
                    text.attr("transform", transform);
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

            graphd3.initSvg = function() {
                d3.select("svg").remove();
                graphd3.width(angular.element(element).width());
                graphd3.height(angular.element(element).height());

                graphd3.svg(d3.select(element[0]).append("svg")
                    .attr("width", graphd3.width())
                    .attr("height", graphd3.height()));
            };

            graphd3.redraw = function() {
                graphd3.initSvg();
                graphd3.initGraph();
            };

            graphd3.resize = function (height, width) {
                if (arguments.length) {
                    graphd3.height(height);
                    graphd3.width(width);
                }
                return graphd3;
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

            graphd3();
        }
    };
}
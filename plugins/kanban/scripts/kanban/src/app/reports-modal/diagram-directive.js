import angular from "angular";
import { createCumulativeChart } from "./cumulative-flow-chart/index";

export default Graph;

Graph.$inject = ["$window", "gettextCatalog"];

function Graph($window, gettextCatalog) {
    return {
        restrict: "E",
        scope: {
            data: "=",
        },
        link: function (scope, element) {
            var CUMULATIVE_CHART_MARGIN_LEFT = 70;
            var CUMULATIVE_CHART_MARGIN_RIGHT = 50;
            var CUMULATIVE_CHART_MARGIN_TOP = 20;
            var CUMULATIVE_CHART_MARGIN_BOTTOM = 30;
            var CUMULATIVE_CHART_LEGEND_MARGIN_WIDTH = 120;
            var CUMULATIVE_CHART_LEGEND_MARGIN_HEIGHT = 20;

            /// moment.js Date format
            var localized_format = gettextCatalog.getString("MM/DD");
            var margin = {
                top: CUMULATIVE_CHART_MARGIN_TOP,
                right: CUMULATIVE_CHART_MARGIN_RIGHT,
                bottom: CUMULATIVE_CHART_MARGIN_BOTTOM,
                left: CUMULATIVE_CHART_MARGIN_LEFT,
            };

            var sizes = getSizesElement();
            var width = sizes.width;
            var height = sizes.height;

            var options = {
                graph_id: element[0].id,
                data: scope.data,
                width: width,
                height: height,
                margin: margin,
                legend_text: gettextCatalog.getString("Nb. of cards"),
                localized_format: localized_format,
            };

            var cumulative_chart = createCumulativeChart(options);
            cumulative_chart.init();

            function resize() {
                var sizes = getSizesElement();
                var width = sizes.width;
                var height = sizes.height;

                cumulative_chart.resize(height, width);

                cumulative_chart
                    .svg()
                    .attr(
                        "width",
                        cumulative_chart.width() + options.margin.left + options.margin.right,
                    )
                    .attr(
                        "height",
                        cumulative_chart.height() + options.margin.top + options.margin.bottom,
                    );

                cumulative_chart
                    .g()
                    .attr(
                        "transform",
                        "translate(" + options.margin.left + "," + options.margin.top + ")",
                    );

                cumulative_chart.redraw();
            }

            angular.element($window).on("resize", resize);

            scope.$on("$destroy", function () {
                angular.element($window).off("resize", resize);
            });

            function getSizesElement() {
                return {
                    width:
                        angular.element(".chart")[0].clientWidth -
                        CUMULATIVE_CHART_LEGEND_MARGIN_WIDTH -
                        margin.left -
                        margin.right,
                    height:
                        angular.element(".chart")[0].clientHeight -
                        CUMULATIVE_CHART_LEGEND_MARGIN_HEIGHT -
                        margin.top -
                        margin.bottom,
                };
            }
        },
    };
}

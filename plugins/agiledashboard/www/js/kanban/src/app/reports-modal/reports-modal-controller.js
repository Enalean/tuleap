angular
    .module('kanban')
    .controller('ReportsModalController', ReportsModalController);

ReportsModalController.$inject = [
    '$modalInstance',
    'moment',
    'd3',
    'gettextCatalog',
    'SharedPropertiesService',
    'DiagramRestService'
];

function ReportsModalController(
    $modalInstance,
    moment,
    d3,
    gettextCatalog,
    SharedPropertiesService,
    DiagramRestService
) {
    var self = this;

    /// moment.js Date format
    var localized_format = gettextCatalog.getString('MM/DD');
    var ISO_DATE_FORMAT  = 'YYYY-MM-DD';

    var schemeCategory20cWithoutLightest = [
        '#3182bd',
        '#6baed6',
        '#9ecae1',
        // '#c6dbef',
        '#e6550d',
        '#fd8d3c',
        '#fdae6b',
        // '#fdd0a2',
        '#31a354',
        '#74c476',
        '#a1d99b',
        // '#c7e9c0',
        '#756bb1',
        '#9e9ac8',
        '#bcbddc',
        // '#dadaeb',
        '#636363',
        '#969696',
        '#bdbdbd'
        // '#d9d9d9'
    ];

    var color_scale = d3.scale.ordinal()
        .range(schemeCategory20cWithoutLightest);

    self.options = {
        chart: {
            type: 'stackedAreaChart',
            margin: {
                top: 20,
                right: 20,
                bottom: 30,
                left: 40
            },
            x: function (d) {
                return moment(d.start_date).valueOf();
            },
            y: function (d) {
                return d.kanban_items_count;
            },
            useVoronoi: false,
            clipEdge: true,
            duration: 100,
            showControls: false,
            useInteractiveGuideline: true,
            xScale: d3.time.scale(),
            xAxis: {
                showMaxMin: false,
                tickFormat: function (d) {
                    return moment(d).format(localized_format);
                }
            },
            yAxis: {
                axisLabel: gettextCatalog.getString('Nb. of cards'),
                axisLabelDistance: -20,
                tickFormat: d3.format('d')
            },
            color: function(data, index) {
                return color_scale(index);
            },
            legend: {
                rightAlign: false,
                margin: {
                    bottom: 20
                },
                dispatch: {
                    legendMouseover: function(d3_legend_data) {
                        triggerAreaHighlight(d3_legend_data, 'mouseover');
                    },
                    legendMouseout: function(d3_legend_data) {
                        triggerAreaHighlight(d3_legend_data, 'mouseout');
                    }
                }
            }
        }
    };

    self.loading      = true;
    self.data         = [];
    self.kanban_label = "";

    self.params  = {
        last_seven_days: {
            number                 : 7,
            time_unit              : 'day',
            interval_between_points: 1
        },
        last_month: {
            number                 : 1,
            time_unit              : 'month',
            interval_between_points: 1
        },
        last_three_months: {
            number                 : 3,
            time_unit              : 'month',
            interval_between_points: 7
        },
        last_six_months: {
            number                 : 6,
            time_unit              : 'month',
            interval_between_points: 7
        },
        last_year: {
            number                 : 1,
            time_unit              : 'year',
            interval_between_points: 7
        }
    };
    self.value_last_data = self.params.last_seven_days;
    self.key_last_data   = 'last_seven_days';

    self.cancel   = function() { $modalInstance.dismiss('cancel'); };
    self.init     = init;
    self.loadData = loadData;
    self.onReady  = onReady;

    self.init();

    function init() {
        self.loadData();
    }

    function loadData() {
        self.loading         = true;
        self.value_last_data = self.params[self.key_last_data];
        self.kanban_label    = SharedPropertiesService.getKanban().label;
        var kanban_id        = SharedPropertiesService.getKanban().id;
        var start_date       = moment().subtract(self.value_last_data.number, self.value_last_data.time_unit).format(ISO_DATE_FORMAT);
        var end_date         = moment().add(1, 'days').format(ISO_DATE_FORMAT);

        DiagramRestService.getCumulativeFlowDiagram(
            kanban_id,
            start_date,
            end_date,
            self.value_last_data.interval_between_points
        ).then(setGraphData)
        .finally(function() {
            self.loading = false;
        });
    }

    function setGraphData(cumulative_flow_data) {
        var color_domain = _.map(cumulative_flow_data.columns, function (data, index, columns) {
            return (columns.length - 1) - index;
        });
        color_scale.domain(color_domain);

        _.forEach(cumulative_flow_data.columns, function (column) {
            column.key = column.label;

            var data_for_today        = _.last(column.values);
            data_for_today.start_date = moment(data_for_today.start_date).subtract(12, 'hours').format();
        });

        self.data = cumulative_flow_data.columns;
    }

    function onReady() {
        var closed_columns_id = getCollapsedKanbanColumnsIds();

        d3.selectAll('.nv-series')
            .each(function (d3_legend_data) {
                if (_.contains(closed_columns_id, d3_legend_data.id)) {
                    var click_event = new MouseEvent('click');
                    this.dispatchEvent(click_event);
                }
            });
    }

    function triggerAreaHighlight(d3_legend_data, event_type) {
        d3.selectAll('.nv-area')
            .each(function(d3_path_data) {
                if (d3_legend_data.id === d3_path_data.id) {
                    var event = new MouseEvent(event_type);
                    this.dispatchEvent(event);

                    return false;
                }
            });
    }

    function getCollapsedKanbanColumnsIds() {
        var kanban = SharedPropertiesService.getKanban();

        var all_columns = [].concat(kanban.columns);
        all_columns.push(kanban.backlog);
        all_columns.push(kanban.archive);

        return _(all_columns)
            .filter({ is_open: false })
            .map(function (column) {
                return column.id.toString();
            })
            .value();
    }
}

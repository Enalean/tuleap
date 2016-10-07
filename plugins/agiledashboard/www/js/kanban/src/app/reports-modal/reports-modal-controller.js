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
    self.loading = true;
    self.chartjs_options = {
        responsive         : true,
        maintainAspectRatio: false,
        hover: {
            onHover: function() {
                angular.element(this.chart.canvas).removeClass('pointer-cursor');
            }
        },
        legend: {
            display : true,
            position: 'right',
            reverse : true,
            onHover : function(event) {
                angular.element(event.target).addClass('pointer-cursor');
            }
        },
        tooltips: {
            // This reverses the order
            itemSort: function(a, b) {
                if (a.datasetIndex < b.datasetIndex) {
                    return 1;
                } else if (a.datasetIndex > b.datasetIndex) {
                    return -1;
                }
                return 0;
            }
        },
        scales: {
            xAxes: [{
                type : 'time',
                ticks: {
                    fontColor: '#9c9c9c'
                },
                time: {
                    displayFormats: {
                        /// moment.js Date format
                        day: gettextCatalog.getString('MM/DD')
                    },
                    minUnit: 'day'
                }
            }],
            yAxes: [{
                position: 'left',
                stacked : true,
                ticks   : {
                    fontColor  : '#9c9c9c',
                    beginAtZero: true
                },
                scaleLabel: {
                    display    : true,
                    labelString: gettextCatalog.getString('Nb. of cards'),
                    fontColor  : '#9c9c9c'
                }
            }]
        }
    };
    self.chartjs_labels = [];
    self.chartjs_data   = [];
    self.chartjs_series = [];
    self.chartjs_colors = [];
    self.kanban_label   = "";
    self.params         = {
        last_seven_days: {
            number: 7,
            time_unit: 'day',
            interval_between_points: 1
        },
        last_month: {
            number: 1,
            time_unit: 'month',
            interval_between_points: 1
        },
        last_three_months: {
            number: 3,
            time_unit: 'month',
            interval_between_points: 7
        },
        last_six_months: {
            number: 6,
            time_unit: 'month',
            interval_between_points: 7
        },
        last_year: {
            number: 1,
            time_unit: 'year',
            interval_between_points: 7
        }
    };
    self.value_last_data = self.params.last_seven_days;
    self.key_last_data   = 'last_seven_days';

    self.cancel         = function() { $modalInstance.dismiss('cancel'); };
    self.init           = init;
    self.loadData       = loadData;

    self.init();

    function init() {
        self.loadData();
    }
    function setChartjsData(cumulative_flow_data) {
        var first_column    = _.first(cumulative_flow_data.columns).values;
        self.chartjs_labels = _.map(first_column, 'start_date');
        self.chartjs_series = _.map(cumulative_flow_data.columns, 'label');
        self.chartjs_data   = _.map(cumulative_flow_data.columns, function(column) {
            return _.map(column.values, 'kanban_items_count');
        });

        var colors = computeGradientColors(self.chartjs_series.length);
        self.chartjs_colors = _(colors).reverse()
            .map(function(color) {
                return {
                    backgroundColor          : color.toString(),
                    pointBackgroundColor     : color.toString(),
                    pointHoverBackgroundColor: color.toString(),
                    borderColor              : color.toString(),
                    pointBorderColor         : color.toString(),
                    pointHoverBorderColor    : color.toString(),
                    pointHitRadius           : 20
                };
            }).value();
    }

    self.schemeCategory20cWithoutLightest = [
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
        '#bdbdbd',
        // '#d9d9d9'
    ];

    function computeGradientColors(nb_columns) {
        var color_scale = d3.scaleOrdinal(self.schemeCategory20cWithoutLightest);

        var colors = [];
        for (var i = 0; i < nb_columns; i++) {
            colors.push(color_scale(i));
        }

        return colors;
    }

    function loadData() {
        self.loading         = true;
        self.value_last_data = self.params[self.key_last_data];
        self.kanban_label    = SharedPropertiesService.getKanban().label;
        var kanban_id        = SharedPropertiesService.getKanban().id;
        var start_date       = moment().subtract(self.value_last_data.number, self.value_last_data.time_unit).format('YYYY-MM-DD');
        var end_date         = moment().format('YYYY-MM-DD');

        DiagramRestService.getCumulativeFlowDiagram(
            kanban_id,
            start_date,
            end_date,
            self.value_last_data.interval_between_points
        ).then(setChartjsData)
         .finally(function() {
             self.loading = false;
         });
    }
}

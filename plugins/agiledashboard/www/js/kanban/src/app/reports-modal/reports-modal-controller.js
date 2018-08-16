import _ from "lodash";

export default ReportsModalController;

ReportsModalController.$inject = [
    "moment",
    "SharedPropertiesService",
    "DiagramRestService",
    "modal_instance"
];

function ReportsModalController(
    moment,
    SharedPropertiesService,
    DiagramRestService,
    modal_instance
) {
    var self = this;

    var ISO_DATE_FORMAT = "YYYY-MM-DD";

    self.loading = true;
    self.data = [];
    self.kanban_label = "";

    self.params = {
        last_seven_days: {
            number: 7,
            time_unit: "day",
            interval_between_points: 1
        },
        last_month: {
            number: 1,
            time_unit: "month",
            interval_between_points: 1
        },
        last_three_months: {
            number: 3,
            time_unit: "month",
            interval_between_points: 7
        },
        last_six_months: {
            number: 6,
            time_unit: "month",
            interval_between_points: 7
        },
        last_year: {
            number: 1,
            time_unit: "year",
            interval_between_points: 7
        }
    };
    self.value_last_data = self.params.last_seven_days;
    self.key_last_data = "last_seven_days";

    self.close = function() {
        modal_instance.tlp_modal.hide();
    };
    self.$onInit = init;
    self.loadData = loadData;

    function init() {
        self.loadData();
    }

    function loadData() {
        self.loading = true;
        self.value_last_data = self.params[self.key_last_data];
        self.kanban_label = SharedPropertiesService.getKanban().label;
        var kanban_id = SharedPropertiesService.getKanban().id;
        var start_date = moment()
            .subtract(self.value_last_data.number, self.value_last_data.time_unit)
            .format(ISO_DATE_FORMAT);
        var end_date = moment()
            .add(1, "days")
            .format(ISO_DATE_FORMAT);

        DiagramRestService.getCumulativeFlowDiagram(
            kanban_id,
            start_date,
            end_date,
            self.value_last_data.interval_between_points
        )
            .then(setGraphData)
            .finally(function() {
                self.loading = false;
            });
    }

    function setGraphData(cumulative_flow_data) {
        var closed_columns_id = getCollapsedKanbanColumnsIds();

        _.forEach(cumulative_flow_data.columns, function(column) {
            var data_for_today = _.last(column.values);
            data_for_today.start_date = moment(data_for_today.start_date)
                .subtract(12, "hours")
                .format();
        });

        _.forEach(cumulative_flow_data.columns, function(column) {
            if (_.contains(closed_columns_id, column.id)) {
                column.activated = false;
            }
        });

        self.data = cumulative_flow_data.columns;
    }

    function getCollapsedKanbanColumnsIds() {
        var kanban = SharedPropertiesService.getKanban();

        var all_columns = [].concat(kanban.columns);
        all_columns.push(kanban.backlog);
        all_columns.push(kanban.archive);

        return _(all_columns)
            .filter({ is_open: false })
            .map(function(column) {
                return column.id.toString();
            })
            .value();
    }
}

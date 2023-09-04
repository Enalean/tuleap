/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

export default ReportsModalController;

ReportsModalController.$inject = [
    "moment",
    "SharedPropertiesService",
    "DiagramRestService",
    "modal_instance",
];

function ReportsModalController(
    moment,
    SharedPropertiesService,
    DiagramRestService,
    modal_instance,
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
            interval_between_points: 1,
        },
        last_month: {
            number: 1,
            time_unit: "month",
            interval_between_points: 1,
        },
        last_three_months: {
            number: 3,
            time_unit: "month",
            interval_between_points: 7,
        },
        last_six_months: {
            number: 6,
            time_unit: "month",
            interval_between_points: 7,
        },
        last_year: {
            number: 1,
            time_unit: "year",
            interval_between_points: 7,
        },
    };
    self.value_last_data = self.params.last_seven_days;
    self.key_last_data = "last_seven_days";

    self.close = function () {
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
        var end_date = moment().add(1, "days").format(ISO_DATE_FORMAT);

        DiagramRestService.getCumulativeFlowDiagram(
            kanban_id,
            start_date,
            end_date,
            self.value_last_data.interval_between_points,
        )
            .then(setGraphData)
            .finally(function () {
                self.loading = false;
            });
    }

    function setGraphData(cumulative_flow_data) {
        var closed_columns_id = getCollapsedKanbanColumnsIds();

        cumulative_flow_data.columns.forEach((column) => {
            const data_for_today = column.values[column.values.length - 1];
            data_for_today.start_date = moment(data_for_today.start_date)
                .subtract(12, "hours")
                .format();

            if (closed_columns_id.includes(column.id)) {
                column.activated = false;
            }
        });

        self.data = cumulative_flow_data.columns;
    }

    function getCollapsedKanbanColumnsIds() {
        const kanban = SharedPropertiesService.getKanban();

        const all_columns = [...kanban.columns, kanban.backlog, kanban.archive];

        return all_columns
            .filter(({ is_open }) => is_open === false)
            .map((column) => column.id.toString());
    }
}

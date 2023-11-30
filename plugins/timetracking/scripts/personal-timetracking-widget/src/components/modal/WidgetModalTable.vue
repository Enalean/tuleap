<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <table class="tlp-table">
        <thead>
            <tr>
                <th>{{ $gettext("Date") }}</th>
                <th>{{ $gettext("Steps") }}</th>
                <th>{{ $gettext("Times") }}</th>
            </tr>
        </thead>
        <tbody v-if="has_times_on_artifact" data-test="table-body-with-row">
            <widget-modal-edit-time
                v-if="personal_store.is_add_mode"
                v-on:swap-mode="setAddMode"
                v-on:validate-time="addNewTime"
                data-test="edit-time-with-row"
            />
            <widget-modal-row
                v-for="time in personal_store.current_times"
                v-bind:key="time.id"
                v-bind:time-data="time"
            />
        </tbody>
        <tbody v-else data-test="table-body-without-row">
            <widget-modal-edit-time
                v-if="personal_store.is_add_mode"
                v-on:swap-mode="setAddMode"
                v-on:validate-time="addNewTime"
                data-test="edit-time-without-row"
            />
            <tr>
                <td colspan="4" class="tlp-table-cell-empty">
                    {{ $gettext("No tracked times have been found for this period and artifact") }}
                </td>
            </tr>
        </tbody>
        <tfoot v-if="has_times_on_artifact" data-test="table-foot">
            <tr>
                <th></th>
                <th></th>
                <th class="tlp-table-last-row timetracking-total-sum">
                    ∑
                    {{ personal_store.get_formatted_aggregated_time(personal_store.current_times) }}
                </th>
            </tr>
        </tfoot>
    </table>
</template>

<script>
import WidgetModalRow from "./WidgetModalRow.vue";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";
import { usePersonalTimetrackingWidgetStore } from "../../store";
import { mapState } from "pinia";

export default {
    name: "WidgetModalTable",
    components: {
        WidgetModalRow,
        WidgetModalEditTime,
    },
    setup() {
        const personal_store = usePersonalTimetrackingWidgetStore();

        return { personal_store };
    },
    computed: {
        ...mapState(usePersonalTimetrackingWidgetStore, [
            "is_add_mode",
            "get_formatted_aggregated_time",
            "current_times",
        ]),
        has_times_on_artifact() {
            return this.personal_store.current_times[0].minutes !== null;
        },
    },
    methods: {
        setAddMode() {
            this.personal_store.setAddMode(false);
        },
        addNewTime(date, artifact_id, time, step) {
            this.personal_store.addTime([date, artifact_id, time, step]);
        },
    },
};
</script>

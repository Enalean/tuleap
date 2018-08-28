/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
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

(<template>
    <table class="tlp-table">
        <thead>
        <tr>
            <th>{{ date_message }}</th>
            <th>{{ steps_label }}</th>
            <th>{{ time_label }}</th>
        </tr>
        </thead>
        <tbody>
        <widget-modal-edit-time
            v-if="is_add_mode"
            v-on:swapMode="setAddMode"
            v-on:validateTime="addNewTime"
        />
        <widget-modal-row
            v-for="time in current_times"
            v-bind:key="time.id"
            v-bind:time-data="time"
        />
        </tbody>
        <tfoot>
        <tr>
            <th></th>
            <th></th>
            <th class="tlp-table-last-row timetracking-total-sum">∑ {{ get_formatted_aggregated_time(current_times) }}</th>
        </tr>
        </tfoot>
    </table>
</template>)

(<script>
import { mapState, mapGetters } from "vuex";
import { gettext_provider } from "../../gettext-provider.js";
import WidgetModalRow from "./WidgetModalRow.vue";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";

export default {
    name: "WidgetModalTable",
    components: { WidgetModalRow, WidgetModalEditTime },
    computed: {
        ...mapState(["is_add_mode", "current_times"]),
        ...mapGetters(["get_formatted_aggregated_time"]),
        time_label: () => gettext_provider.gettext("Times"),
        date_message: () => gettext_provider.gettext("Date"),
        steps_label: () => gettext_provider.gettext("Steps")
    },
    methods: {
        setAddMode() {
            this.$store.commit("setAddMode", false);
        },
        addNewTime(date, artifact_id, time, step) {
            this.$store.dispatch("addTime", [date, artifact_id, time, step]);
        }
    }
};
</script>)

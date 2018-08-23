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
        <tr>
            <td>
                <input type="text"
                       class="tlp-input tlp-input-date"
                       ref="new_date"
                       v-model="date_model"
                       size="11"
                >
            </td>
            <td>
                <input type="text"
                       ref="new_step"
                       class="tlp-input timetracking-details-modal-add-step-field"
                       size="11"
                       maxlength="255"
                       placeholder="preparation"
                >
            </td>
            <td class="timetracking-details-modal-add-time-field">
                <input type="text"
                       v-on:keyup.enter="addNewTime()"
                       ref="new_time"
                       class="tlp-input"
                       size="11"
                       placeholder="hh:mm"
                       required>

                <button class="tlp-button-primary"
                       type="submit"
                        v-on:click="addNewTime"
                ><i class="fa fa-check"></i>
                </button>
                <button class="tlp-button-primary tlp-button-outline"
                       type="button"
                       v-on:click="setAddMode(false)"
                >
                <i class="fa fa-times"></i>
                </button>
            </td>
        </tr>
</template>)
(<script>
import { DateTime } from "luxon";
import { gettext_provider } from "../../gettext-provider.js";
import { datePicker } from "tlp";
import { mapMutations, mapGetters } from "vuex";

export default {
    name: "WigetModalAddTime",
    computed: {
        ...mapGetters(["current_artifact"]),
        date_message: () => gettext_provider.gettext("Date"),
        time_to_add: () => gettext_provider.gettext("Time"),
        step_to_add: () => gettext_provider.gettext("Step"),
        add_time: () => gettext_provider.gettext("Add"),
        date_model() {
            return DateTime.local().toISODate();
        }
    },
    methods: {
        ...mapMutations(["setAddMode"]),
        async addNewTime() {
            await this.$store.dispatch("addTime", [
                this.$refs.new_date.value,
                this.current_artifact.id,
                this.$refs.new_time.value,
                this.$refs.new_step.value
            ]);
        }
    },
    mounted() {
        datePicker(this.$refs.new_date, {
            static: true
        });
    }
};
</script>)

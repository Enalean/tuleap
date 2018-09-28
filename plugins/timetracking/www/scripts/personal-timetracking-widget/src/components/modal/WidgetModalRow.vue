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

<template>
    <widget-modal-edit-time
        v-if="edit_mode"
        v-bind:time-data="timeData"
        v-on:swapMode="swapEditMode"
        v-on:validateTime="editTime"
    />
    <tr v-else>
        <td>{{ timeData.date }}</td>
        <td>{{ timeData.step }}</td>
        <td class="timetracking-details-modal-buttons">
            <span>{{ minutes }}</span>
            <span>
                <button class="tlp-button-primary tlp-button-outline tlp-button-small"
                        v-on:click="swapEditMode()"
                >
                    <i class="fa fa-pencil"></i>
                </button>
            </span>
        </td>
    </tr>
</template>
<script>
import { formatMinutes } from "../../time-formatters.js";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";

export default {
    name: "WidgetModalRow",
    components: { WidgetModalEditTime },
    props: {
        timeData: Object
    },
    data() {
        return {
            edit_mode: false
        };
    },
    computed: {
        minutes() {
            return formatMinutes(this.timeData.minutes);
        }
    },
    methods: {
        swapEditMode() {
            this.edit_mode = !this.edit_mode;
        },
        editTime(date, time_id, time, step) {
            this.$store.dispatch("updateTime", [date, time_id, time, step]);
            this.swapEditMode();
        }
    }
};
</script>

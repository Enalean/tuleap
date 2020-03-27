<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <widget-modal-edit-time
        v-if="edit_mode"
        v-bind:time-data="timeData"
        v-on:swapMode="swapEditMode"
        v-on:validateTime="editTime"
    />
    <tr v-else>
        <td>{{ timeData.date }}</td>
        <td class="timetracking-detail-modal-step" v-bind:title="timeData.step">
            {{ timeData.step }}
        </td>
        <td class="timetracking-details-modal-buttons">
            <span>{{ minutes }}</span>
            <span>
                <button
                    class="tlp-button-primary tlp-button-outline tlp-button-small"
                    v-on:click="swapEditMode()"
                    data-test="timetracking-edit-time"
                >
                    <i class="fa fa-pencil"></i>
                </button>
                <button
                    class="tlp-button-outline tlp-button-small tlp-button-danger"
                    ref="popover_button"
                    data-placement="left"
                    data-trigger="click"
                    data-test="timetracking-delete-time"
                >
                    <i class="fa fa-trash"></i>
                </button>
                <widget-modal-delete-popover
                    v-on:createDeletePopover="createPopover()"
                    v-bind:time-id="timeData.id"
                    ref="delete_popover"
                />
            </span>
        </td>
    </tr>
</template>
<script>
import { formatMinutes } from "../../../../time-formatters.js";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";
import { createPopover } from "tlp";
import WidgetModalDeletePopover from "./WidgetModalDeletePopover.vue";

export default {
    name: "WidgetModalRow",
    components: { WidgetModalEditTime, WidgetModalDeletePopover },
    props: {
        timeData: Object,
    },
    data() {
        return {
            edit_mode: false,
        };
    },
    computed: {
        minutes() {
            return formatMinutes(this.timeData.minutes);
        },
    },
    methods: {
        createPopover() {
            createPopover(this.$refs.popover_button, this.$refs.delete_popover.$el);
        },
        swapEditMode() {
            this.edit_mode = !this.edit_mode;
        },
        editTime(date, time_id, time, step) {
            this.$store.dispatch("updateTime", [date, time_id, time, step]);
            this.swapEditMode();
        },
    },
};
</script>

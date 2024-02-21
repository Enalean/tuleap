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
    <widget-modal-edit-time
        v-if="edit_mode"
        v-bind:time-data="timeData"
        v-on:swap-mode="swapEditMode"
        v-on:validate-time="editTime"
        v-bind:artifact="timeData.artifact"
    />
    <tr v-else>
        <td>{{ time_date }}</td>
        <td class="timetracking-detail-modal-step" v-bind:title="timeData.step">
            {{ timeData.step }}
        </td>
        <td class="timetracking-details-modal-buttons">
            <span>{{ minutes }}</span>
            <span class="timetracking-details-modal-buttons">
                <button
                    class="tlp-button-primary tlp-button-outline tlp-button-small"
                    v-on:click="swapEditMode()"
                    data-test="timetracking-edit-time"
                >
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <widget-modal-delete-popover v-bind:time-id="timeData.id" />
            </span>
        </td>
    </tr>
</template>
<script setup lang="ts">
import {
    formatDateUsingPreferredUserFormat,
    formatMinutes,
} from "@tuleap/plugin-timetracking-time-formatters";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";
import WidgetModalDeletePopover from "./WidgetModalDeletePopover.vue";
import { usePersonalTimetrackingWidgetStore } from "../../store/root";
import { computed, ref } from "vue";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";

const props = defineProps<{
    timeData: PersonalTime;
}>();

const personal_store = usePersonalTimetrackingWidgetStore();

const edit_mode = ref(false);

const minutes = computed((): string => {
    return formatMinutes(props.timeData.minutes);
});
const time_date = computed((): string => {
    return formatDateUsingPreferredUserFormat(
        new Date(props.timeData.date),
        personal_store.user_locale,
    );
});

const swapEditMode = (): void => {
    edit_mode.value = !edit_mode.value;
};
const editTime = (date: string, time_id: number, time: string, step: string): void => {
    personal_store.updateTime(date, time_id, time, step);
    swapEditMode();
};
</script>

<style scoped lang="scss">
.timetracking-detail-modal-step {
    max-width: 125px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.timetracking-details-modal-buttons {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>

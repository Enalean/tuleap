<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="timetracking-artifacts-table">
        <div v-if="personal_store.has_rest_error" class="tlp-alert-danger" data-test="alert-danger">
            {{ error }}
        </div>
        <div
            v-if="personal_store.is_loading"
            class="timetracking-loader"
            data-test="timetracking-loader"
        ></div>
        <table
            v-if="personal_store.can_results_be_displayed"
            class="tlp-table"
            data-test="artifact-table"
        >
            <thead>
                <tr>
                    <th>{{ $gettext("Artifact") }}</th>
                    <th>{{ $gettext("Project") }}</th>
                    <th class="tlp-table-cell-numeric">
                        {{ $gettext("Time") }}
                        <span
                            class="tlp-tooltip tlp-tooltip-left timetracking-time-tooltip"
                            v-bind:data-tlp-tooltip="time_format_tooltip"
                            v-bind:aria-label="time_format_tooltip"
                        >
                            <i class="fa fa-question-circle"></i>
                        </span>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!has_data_to_display">
                    <td colspan="4" class="tlp-table-cell-empty" data-test="empty-tab">
                        {{ $gettext("No tracked time have been found for this period") }}
                    </td>
                </tr>
                <artifact-table-row
                    v-for="(time, index) in personal_store.times"
                    v-bind:key="index"
                    v-bind:time-data="time"
                />
            </tbody>
            <tfoot v-if="has_data_to_display" data-test="table-foot">
                <tr>
                    <th></th>
                    <th></th>
                    <th class="tlp-table-cell-numeric timetracking-total-sum">
                        ∑ {{ personal_store.get_formatted_total_sum }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <div class="tlp-pagination">
            <button
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                data-test="load-more"
                type="button"
                v-if="personal_store.can_load_more"
                v-on:click="loadMore"
                v-bind:disabled="is_loading_more"
            >
                <i v-if="is_loading_more" class="tlp-button-icon fa fa-spinner fa-spin"></i>
                {{ $gettext("Load more") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import ArtifactTableRow from "./WidgetArtifactTableRow.vue";
import { usePersonalTimetrackingWidgetStore } from "../store/root";
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const personal_store = usePersonalTimetrackingWidgetStore();

const is_loading_more = ref(false);

const has_data_to_display = computed((): boolean => {
    return personal_store.times.length > 0;
});
const time_format_tooltip = computed((): string => {
    return $gettext("The time is displayed in hours:minutes");
});
const error = computed((): string => {
    return personal_store.error_message === "error"
        ? $gettext("An error occurred")
        : personal_store.error_message;
});

onMounted((): void => {
    personal_store.loadFirstBatchOfTimes();
});

async function loadMore(): Promise<void> {
    is_loading_more.value = true;
    await personal_store.getTimes();
    is_loading_more.value = false;
}
</script>

<style scoped lang="scss">
.timetracking-artifacts-table {
    margin: var(--tlp-medium-spacing);
}

.timetracking-loader {
    height: 100px;
    background: url("@tuleap/burningparrot-theme/images/spinner.gif") no-repeat center center;
}

.timetracking-total-sum {
    white-space: nowrap;
}

.timetracking-time-tooltip {
    margin: 0 0 0 5px;
    vertical-align: middle;
}
</style>

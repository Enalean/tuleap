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
        <div v-if="has_rest_error" class="tlp-alert-danger" data-test="alert-danger">
            {{ error }}
        </div>
        <div v-if="is_loading" class="timetracking-loader" data-test="timetracking-loader"></div>
        <table v-if="can_results_be_displayed" class="tlp-table" data-test="artifact-table">
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
                    v-for="time in times"
                    v-bind:key="time.id"
                    v-bind:time-data="time"
                />
            </tbody>
            <tfoot v-if="has_data_to_display" data-test="table-foot">
                <tr>
                    <th></th>
                    <th></th>
                    <th class="tlp-table-cell-numeric timetracking-total-sum">
                        ∑ {{ get_formatted_total_sum }}
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
                v-if="can_load_more"
                v-on:click="loadMore"
                v-bind:disabled="is_loading_more"
            >
                <i v-if="is_loading_more" class="tlp-button-icon fa fa-spinner fa-spin"></i>
                {{ $gettext("Load more") }}
            </button>
        </div>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import ArtifactTableRow from "./WidgetArtifactTableRow.vue";

export default {
    name: "WidgetArtifactTable",
    components: { ArtifactTableRow },
    data() {
        return {
            is_loading_more: false,
        };
    },
    computed: {
        ...mapState(["error_message", "times", "is_loading"]),
        ...mapGetters([
            "get_formatted_total_sum",
            "has_rest_error",
            "can_results_be_displayed",
            "can_load_more",
        ]),
        has_data_to_display() {
            return this.times.length > 0;
        },
        time_format_tooltip() {
            return this.$gettext("The time is displayed in hours:minutes");
        },
        error() {
            return this.error_message === "error"
                ? this.$gettext("An error occurred")
                : this.error_message;
        },
    },
    mounted() {
        this.$store.dispatch("loadFirstBatchOfTimes");
    },
    methods: {
        async loadMore() {
            this.is_loading_more = true;
            await this.$store.dispatch("getTimes");
            this.is_loading_more = false;
        },
    },
};
</script>

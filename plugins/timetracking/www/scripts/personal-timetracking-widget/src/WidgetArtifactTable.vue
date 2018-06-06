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
    <div class="timetracking-artifacts-table">
        <div v-if="hasRestError" class="tlp-alert-danger">
            {{ this.rest_error }}
        </div>
        <div v-if="is_loading" class="timetracking-loader"></div>
        <table v-if="canResultsBeDisplayed" class="tlp-table">
            <thead>
                <tr>
                    <th>{{ artifact_label }}</th>
                    <th>{{ project_label }}</th>
                    <th class="tlp-table-cell-numeric">
                        {{ time_label }}
                        <span class="tlp-tooltip tlp-tooltip-left timetracking-time-tooltip"
                            v-bind:data-tlp-tooltip="time_format_tooltip"
                            v-bind:aria-label="time_format_tooltip"
                        >
                            <i class="fa fa-question-circle"></i>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="! hasDataToDisplay">
                    <td colspan="3" class="tlp-table-cell-empty">
                        {{ empty_state }}
                    </td>
                </tr>
                <artifact-table-row v-for="(time, index) in tracked_times"
                    v-bind:key="index"
                    v-bind:time-data="time"
                />
            </tbody>
            <tfoot v-if="hasDataToDisplay">
                <tr>
                    <th></th>
                    <th></th>
                    <th class="tlp-table-cell-numeric timetracking-total-sum">∑ {{ getFormattedTotalSum() }}</th>
                </tr>
            </tfoot>
        </table>
        <div class="tlp-pagination">
            <button
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                type="button"
                v-if="canLoadMore"
                v-on:click="loadMore"
                v-bind:disabled="is_loading_more"
            >
                <i v-if="is_loading_more" class="tlp-button-icon fa fa-spinner fa-spin"></i>
                {{ load_more_label }}
            </button>
        </div>
    </div>
</template>)
(<script>
    import { gettext_provider } from './gettext-provider.js';
    import { getTrackedTimes }  from "./rest-querier.js";
    import { formatMinutes }    from './time-formatters.js';
    import ArtifactTableRow     from "./WidgetArtifactTableRow.vue";

    export default {
        name: "WidgetArtifactTable",
        props: {
            startDate      : String,
            endDate        : String,
            isInReadingMode: Boolean,
            hasQueryChanged: Boolean
        },
        components: { ArtifactTableRow },
        data() {
            return {
                tracked_times    : [],
                rest_error       : '',
                is_loading       : false,
                is_loaded        : false,
                is_loading_more  : false,
                total_times      : 0,
                pagination_offset: 0,
                pagination_limit : 50
            }
        },
        computed: {
            hasRestError() {
                return this.rest_error !== "";
            },
            hasDataToDisplay() {
                return this.tracked_times.length > 0;
            },
            canResultsBeDisplayed() {
                return this.is_loaded
                    && ! this.hasRestError;
            },
            report_state() {
                return [ this.isInReadingMode, this.hasQueryChanged ];
            },
            canLoadMore() {
                return this.pagination_offset < this.total_times;
            },
            time_format_tooltip: () => gettext_provider.gettext('The time is displayed in hours:minutes'),
            empty_state        : () => gettext_provider.gettext('No tracked time have been found for this period'),
            artifact_label     : () => gettext_provider.gettext('Artifact'),
            project_label      : () => gettext_provider.gettext('Project'),
            time_label         : () => gettext_provider.gettext('Time'),
            load_more_label    : () => gettext_provider.gettext('Load more')
        },
        watch: {
            report_state() {
                if (this.isInReadingMode && this.hasQueryChanged) {
                    this.pagination_offset    = 0;
                    this.tracked_times.length = 0;
                    this.loadFirstBatchOfTimes();
                }
            }
        },
        mounted() {
            this.loadFirstBatchOfTimes();
        },
        methods: {
            getFormattedTotalSum() {
                const sum = [].concat(...this.tracked_times)
                    .reduce(
                        (sum, { minutes }) => minutes + sum,
                        0
                    );

                return formatMinutes(sum);
            },
            async loadTimes() {
                try {
                    this.rest_error = '';

                    const { times, total } = await getTrackedTimes(
                        this.startDate,
                        this.endDate,
                        this.pagination_limit,
                        this.pagination_offset
                    );

                    this.tracked_times = this.tracked_times.concat(
                        Object.values(times)
                    );

                    this.pagination_offset += this.pagination_limit;

                    this.total_times = total;
                    this.is_loaded   = true;
                } catch (error) {
                    this.showRestError(error);
                }
            },
            async showRestError(rest_error) {
                try {
                    const { error } = await rest_error.response.json();

                    this.rest_error = error.code + ' ' + error.message;
                } catch (error) {
                    this.rest_error = gettext_provider.gettext('An error occured');
                }
            },
            async loadMore() {
                this.is_loading_more = true;

                await this.loadTimes();

                this.is_loading_more = false;
            },
            async loadFirstBatchOfTimes() {
                this.is_loading = true;

                await this.loadTimes();

                this.is_loading = false;
            }
        }
    }
</script>)

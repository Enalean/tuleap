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
                    <th class="tlp-table-cell-numeric">{{ time_label }}</th>
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
                    <th class="tlp-table-cell-numeric">∑ {{ getFormattedTotalSum() }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</template>)
(<script>
    import { gettext_provider }   from './gettext-provider.js';
    import { getTrackedTimes }    from "./rest-querier.js";
    import { formatMinutesToISO } from './time-formatters.js';
    import ArtifactTableRow       from "./WidgetArtifactTableRow.vue";

    export default {
        name: "WidgetArtifactTable",
        props: {
            startDate      : String,
            endDate        : String,
            isInReadingMode: Boolean,
        },
        components: { ArtifactTableRow },
        data() {
            return {
                tracked_times: [],
                rest_error   : '',
                is_loading   : false,
                is_loaded    : false
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
                return [ this.isInReadingMode ];
            },
            empty_state   : () => gettext_provider.gettext('No tracked time have been found for this period'),
            artifact_label: () => gettext_provider.gettext('Artifact'),
            project_label : () => gettext_provider.gettext('Project'),
            time_label    : () => gettext_provider.gettext('Time')
        },
        watch: {
            report_state() {
                if (this.isInReadingMode === true) {
                    this.loadTimes();
                }
            }
        },
        mounted() {
            this.loadTimes();
        },
        methods: {
            getFormattedTotalSum() {
                const sum = [].concat(...this.tracked_times)
                    .reduce(
                        (sum, { minutes }) => minutes + sum,
                        0
                    );

                return formatMinutesToISO(sum);
            },
            async loadTimes() {
                try {
                    this.is_loading = true;
                    this.rest_error = '';

                    const times = await getTrackedTimes(
                        this.startDate,
                        this.endDate
                    );

                    this.tracked_times = Object.values(times);

                    this.is_loaded = true;
                } catch (error) {
                    this.showRestError(error);
                } finally {
                    this.is_loading = false;
                }
            },
            async showRestError(rest_error) {
                try {
                    const { error } = await rest_error.response.json();

                    this.rest_error = error.code + ' ' + error.message;
                } catch (error) {
                    this.rest_error = gettext_provider.gettext('An error occured');
                }
            }
        }
    }
</script>)

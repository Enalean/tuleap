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
    <div class="cross-tracker-artifacts-table">
        <table class="tlp-table">
            <thead>
                <tr>
                    <th>{{ artifact_label }}</th>
                    <th>{{ project_label }}</th>
                    <th>{{ status_label }}</th>
                    <th>{{ last_update_label }}</th>
                    <th>{{ submitted_by_label }}</th>
                    <th>{{ assigned_to_label }}</th>
                </tr>
            </thead>
            <tbody v-if="is_loading === true">
                <tr>
                    <td colspan="6"><div class="cross-tracker-loader"></div></td>
                </tr>
            </tbody>
            <tbody v-else-if="artifacts.length === 0">
                <tr>
                    <td colspan="6" class="tlp-table-cell-empty">
                        {{ artifacts_empty }}
                    </td>
                </tr>
            </tbody>
            <tbody v-else>
                <artifact-table-row
                    v-for="artifact of artifacts"
                    v-bind:artifact="artifact"
                    v-bind:key="artifact.id"
                ></artifact-table-row>
            </tbody>
        </table>
        <div class="tlp-pagination">
            <button
                    class="tlp-button-primary tlp-button-outline tlp-button-small"
                    type="button"
                    v-if="is_load_more_displayed === true"
                    v-on:click="loadMoreArtifacts"
                    v-bind:disabled="is_loading_more"
            >
                <i v-if="is_loading_more" class="tlp-button-icon fa fa-spinner fa-spin"></i>
                {{ load_more_label }}
            </button>
        </div>
    </div>


</template>)

(<script>
import { gettext_provider } from "./gettext-provider.js";
import ArtifactTableRow from "./ArtifactTableRow.vue";
import { getReportContent, getQueryResult } from "./rest-querier.js";
import moment from "moment";
import { getUserPreferredDateFormat } from "./user-service.js";

export default {
    name: "ArtifactTable",
    components: { ArtifactTableRow },
    props: {
        isReportSaved: Boolean,
        isReportInReadingMode: Boolean,
        reportId: String,
        writingCrossTrackerReport: Object
    },
    data() {
        return {
            is_loading: true,
            artifacts: [],
            is_load_more_displayed: false,
            is_loading_more: false,
            current_offset: 0,
            limit: 30
        };
    },
    computed: {
        artifact_label: () => gettext_provider.gettext("Artifact"),
        project_label: () => gettext_provider.gettext("Project"),
        status_label: () => gettext_provider.gettext("Status"),
        last_update_label: () => gettext_provider.gettext("Last update date"),
        submitted_by_label: () => gettext_provider.gettext("Submitted by"),
        assigned_to_label: () => gettext_provider.gettext("Assigned to"),
        artifacts_empty: () => gettext_provider.gettext("No matching artifacts found"),
        load_more_label: () => gettext_provider.gettext("Load more"),
        report_state() {
            // We just need to react to certain changes in this
            return [this.isReportInReadingMode, this.isReportSaved];
        }
    },
    watch: {
        report_state() {
            if (this.isReportInReadingMode === true) {
                this.refreshArtifactList();
            }
        }
    },
    mounted() {
        this.is_loading = true;
        this.loadArtifacts();
    },
    methods: {
        loadMoreArtifacts() {
            this.is_loading_more = true;
            this.loadArtifacts();
        },

        refreshArtifactList() {
            this.artifacts = [];
            this.current_offset = 0;
            this.is_loading = true;
            this.is_load_more_displayed = false;

            this.loadArtifacts();
        },

        async loadArtifacts() {
            try {
                const { artifacts, total } = await this.getArtifactsFromReportOrUnsavedQuery();

                this.current_offset += artifacts.length;
                this.is_load_more_displayed = this.current_offset < total;

                const new_artifacts = this.formatArtifacts(artifacts);
                this.artifacts = this.artifacts.concat(new_artifacts);
            } catch (error) {
                this.is_load_more_displayed = false;
                this.$emit("restError", error);
                throw error;
            } finally {
                this.is_loading = false;
                this.is_loading_more = false;
            }
        },

        getArtifactsFromReportOrUnsavedQuery() {
            if (this.isReportSaved === true) {
                return getReportContent(this.reportId, this.limit, this.current_offset);
            }

            return getQueryResult(
                this.reportId,
                this.writingCrossTrackerReport.getTrackerIds(),
                this.writingCrossTrackerReport.expert_query,
                this.limit,
                this.current_offset
            );
        },

        formatArtifacts(artifacts) {
            return artifacts.map(artifact => {
                artifact.formatted_last_update_date = moment(artifact.last_update_date).format(
                    getUserPreferredDateFormat()
                );

                return artifact;
            });
        }
    }
};
</script>)

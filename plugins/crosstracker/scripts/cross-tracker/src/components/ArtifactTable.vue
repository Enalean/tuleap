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
    <div class="cross-tracker-artifacts-table">
        <div class="tlp-table-actions" v-if="should_show_export_button">
            <export-button />
        </div>
        <table class="tlp-table">
            <thead>
                <tr>
                    <th v-translate>Artifact</th>
                    <th v-translate>Project</th>
                    <th v-translate>Status</th>
                    <th v-translate>Last update date</th>
                    <th v-translate>Submitted by</th>
                    <th v-translate>Assigned to</th>
                </tr>
            </thead>
            <tbody v-if="is_loading === true">
                <tr>
                    <td colspan="6"><div class="cross-tracker-loader"></div></td>
                </tr>
            </tbody>
            <tbody v-if="is_table_empty" data-test="cross-tracker-no-results">
                <tr>
                    <td colspan="6" class="tlp-table-cell-empty" v-translate>
                        No matching artifacts found
                    </td>
                </tr>
            </tbody>
            <tbody v-else data-test="cross-tracker-results">
                <artifact-table-row
                    v-for="artifact of artifacts"
                    v-bind:artifact="artifact"
                    v-bind:key="artifact.id"
                />
            </tbody>
        </table>
        <div class="tlp-pagination">
            <button
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                type="button"
                v-if="is_load_more_displayed === true"
                v-on:click="loadMoreArtifacts()"
                v-bind:disabled="is_loading_more"
                data-test="load-more"
            >
                <i v-if="is_loading_more" class="tlp-button-icon fas fa-circle-notch fa-spin"></i>
                <translate>Load more</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import moment from "moment";
import ArtifactTableRow from "./ArtifactTableRow.vue";
import ExportButton from "./ExportCSVButton.vue";
import { getReportContent, getQueryResult } from "../api/rest-querier";
import { getUserPreferredDateFormat } from "../user-service";
import type WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { Artifact, ArtifactsCollection } from "../type";
import { State } from "vuex-class";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

@Component({ components: { ArtifactTableRow, ExportButton } })
export default class ArtifactTable extends Vue {
    @Prop({ required: true })
    readonly writingCrossTrackerReport!: WritingCrossTrackerReport;

    @State
    private readonly reading_mode!: boolean;
    @State
    private readonly is_report_saved!: boolean;
    @State
    private readonly report_id!: number;

    is_loading = true;
    artifacts: Artifact[] = [];
    is_load_more_displayed = false;
    is_loading_more = false;
    current_offset = 0;
    limit = 30;

    get report_state(): Array<boolean> {
        // We just need to react to certain changes in this
        return [this.reading_mode, this.is_report_saved];
    }
    get is_table_empty(): boolean {
        return !this.is_loading && this.artifacts.length === 0;
    }
    get should_show_export_button(): boolean {
        return this.reading_mode && this.is_report_saved && !this.is_table_empty;
    }

    @Watch("report_state")
    report_state_value() {
        if (this.reading_mode) {
            this.refreshArtifactList();
        }
    }

    mounted(): void {
        this.is_loading = true;
        this.loadArtifacts();
    }

    loadMoreArtifacts(): void {
        this.is_loading_more = true;
        this.loadArtifacts();
    }

    refreshArtifactList(): void {
        this.artifacts = [];
        this.current_offset = 0;
        this.is_loading = true;
        this.is_load_more_displayed = false;

        this.loadArtifacts();
    }

    async loadArtifacts(): Promise<void> {
        try {
            const { artifacts, total } = await this.getArtifactsFromReportOrUnsavedQuery();

            this.current_offset += this.limit;
            this.is_load_more_displayed = this.current_offset < parseInt(total, 10);

            const new_artifacts = this.formatArtifacts(artifacts);
            this.artifacts = this.artifacts.concat(new_artifacts);
        } catch (error) {
            this.is_load_more_displayed = false;
            if (error instanceof FetchWrapperError) {
                const error_json = await error.response.json();
                if (
                    error_json &&
                    Object.prototype.hasOwnProperty.call(error_json, "error") &&
                    Object.prototype.hasOwnProperty.call(error_json.error, "i18n_error_message")
                ) {
                    this.$store.commit("setErrorMessage", error_json.error.i18n_error_message);
                } else {
                    this.$store.commit("setErrorMessage", this.$gettext("An error occurred"));
                }
            }
        } finally {
            this.is_loading = false;
            this.is_loading_more = false;
        }
    }

    getArtifactsFromReportOrUnsavedQuery(): Promise<ArtifactsCollection> {
        if (this.is_report_saved) {
            return getReportContent(this.report_id, this.limit, this.current_offset);
        }

        return getQueryResult(
            this.report_id,
            this.writingCrossTrackerReport.getTrackerIds(),
            this.writingCrossTrackerReport.expert_query,
            this.limit,
            this.current_offset,
        );
    }

    formatArtifacts(artifacts: Array<Artifact>): Array<Artifact> {
        return artifacts.map((artifact) => {
            artifact.formatted_last_update_date = moment(artifact.last_update_date).format(
                getUserPreferredDateFormat(),
            );

            return artifact;
        });
    }
}
</script>

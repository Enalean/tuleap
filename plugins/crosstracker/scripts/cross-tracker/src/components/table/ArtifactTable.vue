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
    <empty-state
        v-if="is_table_empty"
        v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
    />
    <div class="tlp-table-actions" v-if="should_show_export_button">
        <export-button />
    </div>
    <table class="tlp-table" v-if="is_loading || artifacts.length > 0">
        <thead>
            <tr>
                <th>{{ $gettext("Artifact") }}</th>
                <th>{{ $gettext("Project") }}</th>
                <th>{{ $gettext("Status") }}</th>
                <th>{{ $gettext("Last update date") }}</th>
                <th>{{ $gettext("Submitted by") }}</th>
                <th>{{ $gettext("Assigned to") }}</th>
            </tr>
        </thead>
        <tbody v-if="is_loading">
            <tr>
                <td colspan="6">
                    <div class="cross-tracker-loader"></div>
                </td>
            </tr>
        </tbody>
        <tbody v-if="!is_table_empty" data-test="cross-tracker-results">
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
            v-if="is_load_more_displayed"
            v-on:click="loadMoreArtifacts()"
            v-bind:disabled="is_loading_more"
            data-test="load-more"
        >
            <i
                aria-hidden="true"
                v-if="is_loading_more"
                class="tlp-button-icon fa-solid fa-circle-notch fa-spin"
            ></i>
            {{ $gettext("Load more") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import ArtifactTableRow from "./ArtifactTableRow.vue";
import ExportButton from "../ExportCSVButton.vue";
import { getQueryResult, getReportContent } from "../../api/rest-querier";
import type WritingCrossTrackerReport from "../../writing-mode/writing-cross-tracker-report";
import type { Artifact, ArtifactsCollection } from "../../type";
import {
    DATE_FORMATTER,
    IS_CSV_EXPORT_ALLOWED,
    NOTIFY_FAULT,
    REPORT_ID,
    REPORT_STATE,
} from "../../injection-symbols";
import { ArtifactsRetrievalFault } from "../../domain/ArtifactsRetrievalFault";
import EmptyState from "../EmptyState.vue";

const props = defineProps<{ writing_cross_tracker_report: WritingCrossTrackerReport }>();

const report_state = strictInject(REPORT_STATE);
const date_formatter = strictInject(DATE_FORMATTER);
const is_csv_export_allowed = strictInject(IS_CSV_EXPORT_ALLOWED);
const notifyFault = strictInject(NOTIFY_FAULT);
const report_id = strictInject(REPORT_ID);

const { $gettext } = useGettext();

const is_loading = ref(true);
const artifacts = ref<ReadonlyArray<Artifact>>([]);
const is_load_more_displayed = ref(false);
const is_loading_more = ref(false);
let current_offset = 0;
const limit = 30;

const is_table_empty = computed(() => !is_loading.value && artifacts.value.length === 0);

const should_show_export_button = computed(
    () => is_csv_export_allowed.value && !is_table_empty.value,
);

watch(report_state, () => {
    if (report_state.value === "report-saved" || report_state.value === "result-preview") {
        refreshArtifactList();
    }
});

onMounted(() => {
    is_loading.value = true;
    loadArtifacts();
});

function loadMoreArtifacts(): void {
    is_loading_more.value = true;
    loadArtifacts();
}

function refreshArtifactList(): void {
    artifacts.value = [];
    current_offset = 0;
    is_loading.value = true;
    is_load_more_displayed.value = false;

    loadArtifacts();
}

function loadArtifacts(): void {
    getArtifactsFromReportOrUnsavedQuery()
        .match(
            (collection: ArtifactsCollection) => {
                current_offset += limit;
                is_load_more_displayed.value = current_offset < collection.total;
                const new_artifacts = formatArtifacts(collection.artifacts);
                artifacts.value = artifacts.value.concat(new_artifacts);
            },
            (fault) => {
                is_load_more_displayed.value = false;
                notifyFault(ArtifactsRetrievalFault(fault));
            },
        )
        .then(() => {
            is_loading.value = false;
            is_loading_more.value = false;
        });
}

function getArtifactsFromReportOrUnsavedQuery(): ResultAsync<ArtifactsCollection, Fault> {
    if (report_state.value === "report-saved") {
        return getReportContent(report_id, limit, current_offset);
    }

    return getQueryResult(
        report_id,
        props.writing_cross_tracker_report.getTrackerIds(),
        props.writing_cross_tracker_report.expert_query,
        props.writing_cross_tracker_report.expert_mode,
        limit,
        current_offset,
    );
}

function formatArtifacts(artifacts: ReadonlyArray<Artifact>): ReadonlyArray<Artifact> {
    return artifacts.map((artifact) => {
        artifact.formatted_last_update_date = date_formatter.format(artifact.last_update_date);
        return artifact;
    });
}
</script>

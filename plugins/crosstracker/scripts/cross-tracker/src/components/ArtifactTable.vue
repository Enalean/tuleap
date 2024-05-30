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
                    <th>{{ $gettext("Artifact") }}</th>
                    <th>{{ $gettext("Project") }}</th>
                    <th>{{ $gettext("Status") }}</th>
                    <th>{{ $gettext("Last update date") }}</th>
                    <th>{{ $gettext("Submitted by") }}</th>
                    <th>{{ $gettext("Assigned to") }}</th>
                </tr>
            </thead>
            <tbody v-if="is_loading" key="loading">
                <tr>
                    <td colspan="6"><div class="cross-tracker-loader"></div></td>
                </tr>
            </tbody>
            <tbody v-if="is_table_empty" key="empty" data-test="cross-tracker-no-results">
                <tr>
                    <td colspan="6" class="tlp-table-cell-empty">
                        {{ $gettext("No matching artifacts found") }}
                    </td>
                </tr>
            </tbody>
            <tbody v-else key="loaded" data-test="cross-tracker-results">
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
    </div>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { computed, onMounted, ref, watch } from "vue";
import { useMutations, useState } from "vuex-composition-helpers";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import moment from "moment";
import ArtifactTableRow from "./ArtifactTableRow.vue";
import ExportButton from "./ExportCSVButton.vue";
import { getQueryResult, getReportContent } from "../api/rest-querier";
import { getUserPreferredDateFormat } from "../user-service";
import type WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report";
import type { Artifact, ArtifactsCollection, State } from "../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const props = defineProps<{ writingCrossTrackerReport: WritingCrossTrackerReport }>();

const { reading_mode, is_report_saved, report_id } = useState<
    Pick<State, "reading_mode" | "is_report_saved" | "report_id">
>(["reading_mode", "is_report_saved", "report_id"]);
const { setErrorMessage } = useMutations(["setErrorMessage"]);
const gettext_provider = useGettext();

const is_loading = ref(true);
let artifacts: Ref<Artifact[]> = ref([]);
const is_load_more_displayed = ref(false);
const is_loading_more = ref(false);
let current_offset = 0;
const limit = 30;

const is_table_empty = computed(() => !is_loading.value && artifacts.value.length === 0);

const should_show_export_button = computed(
    () => reading_mode.value === true && is_report_saved.value === true && !is_table_empty.value,
);

watch(
    () => [reading_mode.value, is_report_saved.value],
    () => {
        if (reading_mode.value === true) {
            refreshArtifactList();
        }
    },
);

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

async function loadArtifacts(): Promise<void> {
    try {
        const result = await getArtifactsFromReportOrUnsavedQuery();

        current_offset += limit;
        is_load_more_displayed.value = current_offset < parseInt(result.total, 10);

        const new_artifacts = formatArtifacts(result.artifacts);
        artifacts.value = artifacts.value.concat(new_artifacts);
    } catch (error) {
        is_load_more_displayed.value = false;
        if (error instanceof FetchWrapperError) {
            const error_json = await error.response.json();
            if (error_json && "error" in error_json && "i18n_error_message" in error_json.error) {
                setErrorMessage(error_json.error.i18n_error_message);
            } else {
                setErrorMessage(gettext_provider.$gettext("An error occurred"));
            }
        }
    } finally {
        is_loading.value = false;
        is_loading_more.value = false;
    }
}

function getArtifactsFromReportOrUnsavedQuery(): Promise<ArtifactsCollection> {
    if (is_report_saved.value === true) {
        return getReportContent(report_id.value, limit, current_offset);
    }

    return getQueryResult(
        report_id.value,
        props.writingCrossTrackerReport.getTrackerIds(),
        props.writingCrossTrackerReport.expert_query,
        limit,
        current_offset,
    );
}

function formatArtifacts(artifacts: ReadonlyArray<Artifact>): ReadonlyArray<Artifact> {
    return artifacts.map((artifact) => {
        artifact.formatted_last_update_date = moment(artifact.last_update_date).format(
            getUserPreferredDateFormat(),
        );

        return artifact;
    });
}
</script>

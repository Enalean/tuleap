<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <div class="cross-tracker-reading-mode">
        <div
            class="reading-mode-report"
            v-bind:class="{ disabled: !is_user_admin }"
            v-on:click="switchToWritingMode"
            data-test="cross-tracker-reading-mode"
        >
            <tracker-list-reading-mode
                v-bind:reading-cross-tracker-report="props.readingCrossTrackerReport"
                data-test="tracker-list-reading-mode"
            />
            <div
                class="reading-mode-query"
                v-if="is_expert_query_not_empty"
                data-test="tql-reading-mode-query"
            >
                {{ props.readingCrossTrackerReport.expert_query }}
            </div>
        </div>
        <div class="reading-mode-actions" v-if="!is_report_saved">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline reading-mode-actions-cancel"
                v-on:click="cancelReport()"
                data-test="cross-tracker-cancel-report"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="saveReport()"
                v-bind:class="{ disabled: is_save_disabled }"
                data-test="cross-tracker-save-report"
            >
                <i
                    aria-hidden="true"
                    class="tlp-button-icon fa-solid"
                    v-bind:class="{
                        'fa-circle-notch fa-spin': is_loading,
                        'fa-save': !is_loading,
                    }"
                ></i>
                {{ $gettext("Save report") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import { useGetters, useMutations, useState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import TrackerListReadingMode from "./TrackerListReadingMode.vue";
import { updateReport } from "../api/rest-querier";
import type ReadingCrossTrackerReport from "./reading-cross-tracker-report";
import type { State } from "../type";
import type BackendCrossTrackerReport from "../backend-cross-tracker-report";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const { $gettext } = useGettext();

const props = defineProps<{
    readingCrossTrackerReport: ReadingCrossTrackerReport;
    backendCrossTrackerReport: BackendCrossTrackerReport;
}>();

const emit = defineEmits<{
    (e: "switch-to-writing-mode"): void;
    (e: "saved"): void;
}>();

const { is_report_saved, report_id, is_user_admin } = useState<
    Pick<State, "is_report_saved" | "report_id" | "is_user_admin">
>(["is_report_saved", "report_id", "is_user_admin"]);

const { has_error_message } = useGetters(["has_error_message"]);

const { setErrorMessage, discardUnsavedReport } = useMutations([
    "setErrorMessage",
    "discardUnsavedReport",
]);

const is_loading = ref(false);

const is_save_disabled = computed(
    () => is_loading.value === true || has_error_message.value === true,
);

const is_expert_query_not_empty = computed(
    () => props.readingCrossTrackerReport.expert_query !== "",
);

function switchToWritingMode(): void {
    if (!is_user_admin.value) {
        return;
    }
    emit("switch-to-writing-mode");
}

async function saveReport(): Promise<void> {
    if (is_save_disabled.value) {
        return;
    }

    is_loading.value = true;

    props.backendCrossTrackerReport.duplicateFromReport(props.readingCrossTrackerReport);
    const tracker_ids = props.backendCrossTrackerReport.getTrackerIds();
    const new_expert_query = props.backendCrossTrackerReport.getExpertQuery();
    try {
        const { trackers, expert_query } = await updateReport(
            report_id.value,
            tracker_ids,
            new_expert_query,
        );
        props.backendCrossTrackerReport.init(trackers, expert_query);

        emit("saved");
    } catch (error) {
        if (error instanceof FetchWrapperError) {
            const error_json = await error.response.json();
            setErrorMessage(error_json.error.message);
        }
    } finally {
        is_loading.value = false;
    }
}

function cancelReport(): void {
    props.readingCrossTrackerReport.duplicateFromReport(props.backendCrossTrackerReport);
    discardUnsavedReport();
}
</script>

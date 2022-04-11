<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
  -
  -->

<template>
    <label class="tlp-label">
        {{ $gettext("Report") }}
        <select
            v-model="report"
            class="tlp-select"
            v-bind:disabled="tracker_id === null || is_processing"
        >
            <optgroup
                v-if="personal_reports.length > 0"
                v-bind:label="
                    $ngettext('Personal report', 'Personal reports', personal_reports.length)
                "
            >
                <option
                    v-for="personal_report in personal_reports"
                    v-bind:key="personal_report.id"
                    v-bind:value="personal_report"
                >
                    {{ personal_report.label }}
                </option>
            </optgroup>
            <optgroup
                v-if="public_reports.length > 0"
                v-bind:label="$ngettext('Public report', 'Public reports', public_reports.length)"
            >
                <option
                    v-for="public_report in public_reports"
                    v-bind:key="public_report.id"
                    v-bind:value="public_report"
                >
                    {{ public_report.label }}
                </option>
            </optgroup>
        </select>
    </label>
</template>
<script lang="ts" setup>
import { computed } from "vue";
import { getTrackerReports as getTrackerReportsFromAPI } from "../rest-querier";
import { usePromise } from "../Helpers/use-promise";
import type { SelectedReport } from "../type";
import type { TrackerReportResponse } from "@tuleap/plugin-tracker-rest-api-types/src";

const props = defineProps<{ tracker_id: number | null; report: SelectedReport | null }>();
const emit = defineEmits<{
    (e: "update:report", value: SelectedReport | null): void;
}>();

const default_tracker_reports: TrackerReportResponse[] = [];
function getTrackerReports(tracker_id: number | null): Promise<TrackerReportResponse[]> {
    if (tracker_id === null) {
        return Promise.resolve(default_tracker_reports);
    }
    return getTrackerReportsFromAPI(tracker_id);
}

const { is_processing, data } = usePromise(
    default_tracker_reports,
    computed(() => {
        return getTrackerReports(props.tracker_id);
    })
);

const report = computed({
    get(): SelectedReport | null {
        const retrieved_report = data.value.find(
            (retrieved_report) => retrieved_report.id === props.report?.id
        );
        if (retrieved_report !== undefined) {
            emit("update:report", retrieved_report);
            return retrieved_report;
        }
        return props.report;
    },
    set(value: SelectedReport | null) {
        emit("update:report", value);
    },
});

const public_reports = computed(() => {
    return data.value.filter((report) => report.is_public);
});

const personal_reports = computed(() => {
    return data.value.filter((report) => !report.is_public);
});
</script>

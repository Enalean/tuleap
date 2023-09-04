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
    <div
        class="tlp-form-element"
        v-bind:class="{ 'tlp-form-element-disabled': tracker_id === null }"
    >
        <label class="tlp-label" v-bind:for="select_element_id">
            {{ $gettext("Report") }}
        </label>
        <select
            v-bind:id="select_element_id"
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
    </div>
</template>
<script lang="ts" setup>
import { computed, watch } from "vue";
import { getTrackerReports as getTrackerReportsFromAPI } from "../rest-querier";
import { usePromise } from "../Helpers/use-promise";
import type { SelectedReport } from "../type";
import type { TrackerReportResponse } from "@tuleap/plugin-tracker-rest-api-types";
import { generateElementID } from "../Helpers/id-element-generator";

const props = defineProps<{ tracker_id: number | null; report: SelectedReport | null }>();
const emit = defineEmits<{
    (e: "update:report", value: SelectedReport | null): void;
}>();

const select_element_id = generateElementID();

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
    }),
);

const report = computed({
    get(): SelectedReport | null {
        return props.report;
    },
    set(value: SelectedReport | null) {
        emit("update:report", value);
    },
});

watch(
    () => data.value,
    (report_responses) => {
        const selected_report = report_responses.find(
            (report_response) => report_response.id === props.report?.id,
        );
        if (selected_report) {
            report.value = selected_report;
            return;
        }
        const default_report = report_responses.find(
            (report_response) => report_response.is_default,
        );
        if (default_report) {
            report.value = default_report;
            return;
        }

        const first_public_report = report_responses.find(
            (report_response) => report_response.is_public,
        );
        if (first_public_report) {
            report.value = first_public_report;
            return;
        }

        const first_private_report = report_responses.find(
            (report_response) => !report_response.is_public,
        );
        if (first_private_report) {
            report.value = first_private_report;
            return;
        }

        report.value = null;
    },
);

const public_reports = computed(() => {
    return data.value.filter((report) => report.is_public);
});

const personal_reports = computed(() => {
    return data.value.filter((report) => !report.is_public);
});
</script>

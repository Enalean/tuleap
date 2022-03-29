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
        <select v-model="report_id" class="tlp-select">
            <optgroup
                v-if="personal_reports.length > 0"
                v-bind:label="
                    $ngettext('Personal report', 'Personal reports', personal_reports.length)
                "
            >
                <option
                    v-for="report in personal_reports"
                    v-bind:key="report.id"
                    v-bind:value="report.id"
                >
                    {{ report.name }}
                </option>
            </optgroup>
            <optgroup
                v-if="public_reports.length > 0"
                v-bind:label="$ngettext('Public report', 'Public reports', public_reports.length)"
            >
                <option
                    v-for="report in public_reports"
                    v-bind:key="report.id"
                    v-bind:value="report.id"
                >
                    {{ report.name }}
                </option>
            </optgroup>
        </select>
    </label>
</template>
<script lang="ts" setup>
import type { TrackerReport } from "../type";
import { readonly, computed } from "vue";

const props =
    defineProps<{ current_tracker_reports: ReadonlyArray<TrackerReport>; report_id: number }>();
const emit = defineEmits<{
    (e: "update:report_id", value: number): void;
}>();

const report_id = computed({
    get(): number {
        return props.report_id;
    },
    set(value: number) {
        emit("update:report_id", value);
    },
});

const public_reports = readonly(
    props.current_tracker_reports.filter((report: TrackerReport) => report.is_public)
);
const personal_reports = readonly(
    props.current_tracker_reports.filter((report: TrackerReport) => !report.is_public)
);
</script>

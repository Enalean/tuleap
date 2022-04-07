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
    <div>
        <h2 class="tlp-modal-subtitle">
            {{ $gettext("First level") }}
        </h2>
        <div class="tlp-form-element">
            <artifact-link-types-selector
                v-model:artifact_link_types="artifact_link_types"
                v-bind:tracker_id="tracker_id"
            />
        </div>
        <div class="tlp-form-element">
            <tracker-report-selector v-model:report="report" v-bind:tracker_id="tracker_id" />
        </div>
    </div>
</template>
<script lang="ts" setup>
import type { SelectedReport } from "../type";
import { computed } from "vue";
import TrackerReportSelector from "./TrackerReportSelector.vue";
import ArtifactLinkTypesSelector from "./ArtifactLinkTypesSelector.vue";

const props = defineProps<{
    tracker_id: number;
    report: SelectedReport;
    artifact_link_types: string[];
}>();
const emit = defineEmits<{
    (e: "update:report", value: SelectedReport): void;
    (e: "update:artifact_link_types", value: string[]): void;
}>();

const report = computed({
    get(): SelectedReport {
        return props.report;
    },
    set(value: SelectedReport) {
        emit("update:report", value);
    },
});

const artifact_link_types = computed({
    get(): string[] {
        return props.artifact_link_types;
    },
    set(value: string[]) {
        emit("update:artifact_link_types", value);
    },
});
</script>

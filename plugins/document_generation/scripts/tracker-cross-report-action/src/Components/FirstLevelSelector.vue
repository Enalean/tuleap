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
            <label class="tlp-label">
                {{ $gettext("Link type") }}
                <select v-model="artifact_link_type" class="tlp-select">
                    <option value="">{{ $gettext("All link types") }}</option>
                    <option
                        v-for="art_link in current_tracker_artifact_link_not_empty_types"
                        v-bind:key="art_link.shortname"
                        v-bind:value="art_link.shortname"
                    >
                        {{ art_link.forward_label }}
                    </option>
                </select>
            </label>
        </div>
        <div class="tlp-form-element">
            <tracker-report-selector
                v-model:report_id="report_id"
                v-bind:current_tracker_reports="current_tracker_reports"
            />
        </div>
    </div>
</template>
<script lang="ts" setup>
import type { ArtifactLinkType, TrackerReport } from "../type";
import { computed } from "vue";
import TrackerReportSelector from "./TrackerReportSelector.vue";

const props = defineProps<{
    current_tracker_reports: ReadonlyArray<TrackerReport>;
    current_tracker_artifact_link_types: ReadonlyArray<ArtifactLinkType>;
    report_id: number;
    artifact_link_type: string;
}>();
const emit = defineEmits<{
    (e: "update:report_id", value: number): void;
    (e: "update:artifact_link_type", value: string): void;
}>();

const report_id = computed({
    get(): number {
        return props.report_id;
    },
    set(value: number) {
        emit("update:report_id", value);
    },
});

const current_tracker_artifact_link_not_empty_types = computed(
    (): ReadonlyArray<ArtifactLinkType> => {
        return props.current_tracker_artifact_link_types.filter(
            (art_link_type) => art_link_type.shortname !== ""
        );
    }
);

const artifact_link_type = computed({
    get(): string {
        return props.artifact_link_type;
    },
    set(value: string) {
        emit("update:artifact_link_type", value);
    },
});
</script>

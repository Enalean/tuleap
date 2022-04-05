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
                <select v-model="artifact_link_types" class="tlp-select" multiple>
                    <option
                        v-for="art_link in current_tracker_artifact_link_types"
                        v-bind:key="art_link.shortname"
                        v-bind:value="art_link.shortname"
                    >
                        {{
                            art_link.shortname === NO_TYPE_SHORTNAME
                                ? $gettext("No type")
                                : art_link.forward_label
                        }}
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

const NO_TYPE_SHORTNAME = "";

const props = defineProps<{
    current_tracker_reports: ReadonlyArray<TrackerReport>;
    current_tracker_artifact_link_types: ReadonlyArray<ArtifactLinkType>;
    report_id: number;
    artifact_link_types: string[];
}>();
const emit = defineEmits<{
    (e: "update:report_id", value: number): void;
    (e: "update:artifact_link_types", value: string[]): void;
}>();

const report_id = computed({
    get(): number {
        return props.report_id;
    },
    set(value: number) {
        emit("update:report_id", value);
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

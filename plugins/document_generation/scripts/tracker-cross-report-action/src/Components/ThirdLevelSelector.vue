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
    <level-selector>
        <h2 class="tlp-modal-subtitle">
            {{ $gettext("Third level") }}
        </h2>
        <div class="tlp-form-element">
            <project-selector v-model:project_id="project_id" />
        </div>
        <div class="tlp-form-element">
            <tracker-selector v-model:tracker="tracker" v-bind:project_id="project_id" />
        </div>
        <div class="tlp-form-element">
            <tracker-report-selector
                v-model:report="report"
                v-bind:tracker_id="tracker?.id ?? null"
            />
        </div>
    </level-selector>
</template>
<script lang="ts" setup>
import { computed, ref } from "vue";
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";
import type { SelectedReport, SelectedTracker } from "../type";
import TrackerReportSelector from "./TrackerReportSelector.vue";
import LevelSelector from "./LevelSelector.vue";

const props = defineProps<{
    tracker: SelectedTracker | null;
    report: SelectedReport | null;
}>();
const emit = defineEmits<{
    (e: "update:tracker", value: SelectedTracker | null): void;
    (e: "update:report", value: SelectedReport | null): void;
}>();

const tracker = computed({
    get(): SelectedTracker | null {
        return props.tracker;
    },
    set(value: SelectedTracker | null) {
        emit("update:tracker", value);
    },
});
const report = computed({
    get(): SelectedReport | null {
        return props.report;
    },
    set(value: SelectedReport | null) {
        emit("update:report", value);
    },
});

const project_id = ref<number | null>(null);
</script>

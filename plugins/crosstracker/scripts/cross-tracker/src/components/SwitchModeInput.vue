<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="switch-to-expert">
        <div class="tlp-switch tlp-switch-mini">
            <input
                type="checkbox"
                v-bind:id="switch_to_expert_id"
                class="tlp-switch-checkbox"
                v-bind:checked="writing_cross_tracker_report.expert_mode"
                v-on:click="switchMode"
                data-test="switch-to-expert-input"
            />
            <label v-bind:for="switch_to_expert_id" class="tlp-switch-button"></label>
        </div>
        <label class="tlp-label switch-label" v-bind:for="switch_to_expert_id">{{
            $gettext("Expert mode")
        }}</label>
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { REPORT_ID } from "../injection-symbols";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type WritingCrossTrackerReport from "../writing-mode/writing-cross-tracker-report";
const props = defineProps<{
    writing_cross_tracker_report: WritingCrossTrackerReport;
}>();

const report_id = strictInject(REPORT_ID);

const { $gettext } = useGettext();

const switch_to_expert_id = computed((): string => {
    return "toggle" + report_id;
});

function switchMode(): void {
    props.writing_cross_tracker_report.toggleExpertMode();
}
</script>

<style scoped lang="scss">
.switch-to-expert {
    display: flex;
}

.switch-label {
    margin: 0 0 0 var(--tlp-small-spacing);
}
</style>

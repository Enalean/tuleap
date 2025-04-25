<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
        class="tlp-form-element roadmap-gantt-control"
        v-bind:class="{ 'tlp-form-element-disabled': is_disabled }"
    >
        <label class="tlp-label roadmap-gantt-control-label" v-bind:for="id">
            {{ $gettext("Timescale") }}
        </label>
        <select
            class="tlp-select tlp-select-small tlp-select-adjusted"
            v-bind:id="id"
            v-on:change="updateTimePeriod"
            data-test="select-timescale"
            v-bind:disabled="is_disabled"
        >
            <option value="week" v-bind:selected="value === 'week'" data-test="week">
                {{ $gettext("Week") }}
            </option>
            <option value="month" v-bind:selected="value === 'month'" data-test="month">
                {{ $gettext("Month") }}
            </option>
            <option value="quarter" v-bind:selected="value === 'quarter'" data-test="quarter">
                {{ $gettext("Quarter") }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedGetters } from "vuex-composition-helpers";
import { getUniqueId } from "../../../helpers/uniq-id-generator";
import type { TimeScale } from "../../../type";

const { has_at_least_one_row_shown } = useNamespacedGetters("tasks", [
    "has_at_least_one_row_shown",
]);

defineProps<{
    value: TimeScale;
}>();

const emit = defineEmits<{
    (e: "input", value: string | null): void;
}>();

const id = computed(() => getUniqueId("roadmap-gantt-timescale"));
const is_disabled = computed(() => !has_at_least_one_row_shown.value);

function updateTimePeriod(event: Event): void {
    if (event.target instanceof HTMLSelectElement) {
        const value: string | null = event.target.value;
        emit("input", value);
    }
}
</script>

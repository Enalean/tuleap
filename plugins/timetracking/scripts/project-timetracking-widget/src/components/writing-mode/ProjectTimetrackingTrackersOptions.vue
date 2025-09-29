<!--
  - Copyright Enalean (c) 2019 - Present. All rights reserved.
  -
  -  Tuleap and Enalean names and logos are registrated trademarks owned by
  -  Enalean SAS. All other trademarks or names are properties of their respective
  -  owners.
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
  -
  -->
<template>
    <select
        class="project-timetracking-trackers-selector-input tlp-select"
        id="tracker"
        name="tracker"
        ref="select"
        v-bind:disabled="is_tracker_select_disabled"
        v-on:input="setSelected($event)"
        data-test="project-timetracking-tracker-selector"
    >
        <option v-bind:value="null">
            {{ $gettext("Please choose...") }}
        </option>
        <option
            v-for="tracker in project_timetracking_store.trackers"
            v-bind:disabled="tracker.disabled"
            v-bind:value="tracker.id"
            v-bind:key="tracker.id"
        >
            {{ tracker.label }}
        </option>
    </select>
</template>
<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { REPORT_ID } from "../../injection-symbols";
import { useProjectTimetrackingWidgetStore } from "../../store";

const { $gettext } = useGettext();

const project_timetracking_store = useProjectTimetrackingWidgetStore(strictInject(REPORT_ID))();

const select = ref<HTMLSelectElement>();

const emit = defineEmits<{
    (e: "input", tracker_id: string): void;
}>();

watch(
    () => project_timetracking_store.is_added_tracker,
    () => {
        if (select.value) {
            select.value.options.selectedIndex = 0;
        }
    },
    { deep: true },
);

const is_tracker_select_disabled = computed(() => project_timetracking_store.trackers.length === 0);

function setSelected(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }

    emit("input", event.target.value);
}
</script>

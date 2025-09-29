<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  -->
<template>
    <select
        class="tlp-select project-timetracking-project-selector-input"
        id="project"
        name="project"
        ref="select_project"
        v-on:change="getTrackers()"
        data-test="project-timetracking-project-list"
    >
        <option selected value="">
            {{ $gettext("Please choose...") }}
        </option>
        <option v-for="project in projects" v-bind:key="project.id" v-bind:value="project.id">
            {{ project.label }}
        </option>
    </select>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { REPORT_ID } from "../../injection-symbols";
import { useProjectTimetrackingWidgetStore } from "../../store";

const { $gettext } = useGettext();
defineProps<{
    projects: ProjectReference[];
}>();

const project_timetracking_store = useProjectTimetrackingWidgetStore(strictInject(REPORT_ID))();

const select_project = ref<HTMLSelectElement | null>(null);

onMounted(() => {
    getTrackers();
});

function getTrackers(): void {
    const options = select_project.value?.options;

    if (!options) {
        return;
    }

    if (options[options.selectedIndex] && options[options.selectedIndex].value !== "") {
        project_timetracking_store.getTrackers(Number(options[options.selectedIndex].value));
    }
}
</script>

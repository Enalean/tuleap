<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="move-artifact-project-selector-section">
        <label for="move-artifact-project-selector">
            {{ $gettext("Destination project") }}
            <span class="highlight">*</span>
        </label>

        <select
            id="move-artifact-project-selector"
            name="move-artifact-project-selector"
            data-test="move-artifact-project-selector"
            v-model="selected_project_id"
            v-on:change="selectors_store.loadTrackerList(selected_project_id)"
            ref="move_artifact_project_selector"
        >
            <option v-for="project in projects" v-bind:key="project.id" v-bind:value="project.id">
                {{ project.label }}
            </option>
        </select>
    </div>
</template>
<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { createListPicker } from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import { useSelectorsStore } from "../stores/selectors";
import { PROJECT_ID } from "../injection-symbols";

const { $gettext } = useGettext();

const selectors_store = useSelectorsStore();

const projects = selectors_store.projects;

const current_project_id = strictInject(PROJECT_ID);
const selected_project_id = ref(current_project_id);
const list_picker = ref<ListPicker | undefined>();
const move_artifact_project_selector = ref<HTMLSelectElement>();

selectors_store.saveSelectedProjectId(current_project_id);
selectors_store.loadTrackerList(current_project_id);

onMounted(() => {
    if (!(move_artifact_project_selector.value instanceof HTMLSelectElement)) {
        return;
    }

    list_picker.value = createListPicker(move_artifact_project_selector.value, {
        locale: document.body.dataset.userLocale,
        is_filterable: true,
        placeholder: $gettext("Choose project..."),
    });
});

onBeforeUnmount(() => {
    list_picker.value?.destroy();
});
</script>

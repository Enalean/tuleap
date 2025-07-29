<!--
    - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="card-content">
        <div class="tlp-form-element">
            <label class="tlp-label" for="project-selector">{{ $gettext("Project") }}</label>
            <select
                data-test="project-select"
                v-model="selected_project_model"
                v-on:change="setTrackers"
                class="tlp-select"
                id="project-selector"
            >
                <option v-bind:value="null" disabled>
                    {{ $gettext("Choose a project...") }}
                </option>
                <option
                    v-for="project in available_projects"
                    v-bind:value="project"
                    v-bind:key="project.id"
                    v-bind:selected="
                        selected_project_model !== null && selected_project_model.id === project.id
                    "
                >
                    {{ project.name }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{
                'tlp-form-element-disabled': selected_project === null,
            }"
        >
            <label class="tlp-label" for="template-selector">{{ $gettext("Template") }}</label>
            <select
                data-test="project-tracker-select"
                v-model="selected_tracker_model"
                v-on:change="setTrackerTemplate(selected_tracker_model)"
                v-bind:disabled="selected_project === null"
                class="tlp-select"
                id="template-selector"
                name="tracker-id-from-project"
            >
                <option disabled v-bind:value="null">
                    {{ $gettext("Choose a tracker...") }}
                </option>
                <option
                    v-for="tracker in trackers_of_selected_project"
                    v-bind:value="tracker"
                    v-bind:key="tracker.id"
                    v-bind:selected="
                        selected_tracker_model !== null && selected_tracker_model.id === tracker.id
                    "
                >
                    {{ tracker.name }}
                </option>
            </select>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import type { ProjectWithTrackers, Tracker } from "../../../../../store/type";
import { useStore } from "vuex-composition-helpers";

const store = useStore();

const selected_project_model = ref<ProjectWithTrackers | null>(null);
const trackers_of_selected_project = ref<Tracker[]>([]);
const selected_tracker_model = ref<Tracker | null>(null);

const trackers_from_other_projects = computed(() => store.state.trackers_from_other_projects);
const selected_project = computed(() => store.state.selected_project);
const selected_project_tracker_template = computed(
    () => store.state.selected_project_tracker_template,
);

const available_projects = computed(() => {
    const projects = trackers_from_other_projects;
    return projects.value.sort((a: ProjectWithTrackers, b: ProjectWithTrackers) =>
        a.name.localeCompare(b.name, undefined, { numeric: true }),
    );
});

function setTrackerTemplate(selected_tracker_model: Tracker | null): void {
    store.commit("setSelectedProjectTrackerTemplate", selected_tracker_model);
}

const setTrackers = (): void => {
    if (selected_project_model.value === null) {
        return;
    }
    store.commit("setSelectedProject", selected_project_model.value);
    store.commit("setSelectedProjectTrackerTemplate", null);
    trackers_of_selected_project.value = selected_project_model.value.trackers;
    selected_tracker_model.value = null;
};

onMounted(() => {
    if (selected_project_tracker_template.value && selected_project.value) {
        selected_project_model.value = selected_project.value;
        selected_tracker_model.value = selected_project_tracker_template.value;
        trackers_of_selected_project.value = selected_project.value.trackers;
    }
});
</script>

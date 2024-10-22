<!--
  - Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
    <div class="cross-tracker-selector">
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_project_select_disabled }"
            data-test="cross-tracker-selector-global"
        >
            <label class="tlp-label" for="project">
                {{ $gettext("Project") }}
                <i aria-hidden="true" class="fa-solid fa-asterisk"></i>
            </label>
            <select
                class="cross-tracker-selector-project-input tlp-select"
                id="project"
                name="project"
                v-bind:disabled="is_project_select_disabled"
                v-model="selected_project"
                data-test="cross-tracker-selector-project"
            >
                <option v-for="project of projects" v-bind:value="project" v-bind:key="project.id">
                    {{ project.label }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_tracker_select_disabled }"
        >
            <label class="tlp-label" for="tracker">
                {{ $gettext("Tracker") }}
                <i aria-hidden="true" class="fa-solid fa-asterisk"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-append">
                <select
                    class="cross-tracker-selector-tracker-input tlp-select"
                    id="tracker"
                    name="tracker"
                    v-bind:disabled="is_tracker_select_disabled"
                    v-model="tracker_to_add"
                    data-test="cross-tracker-selector-tracker"
                >
                    <option v-bind:value="null" class="cross-tracker-please-choose-option">
                        {{ $gettext("Please choose…") }}
                    </option>
                    <option
                        v-for="tracker of tracker_options"
                        v-bind:value="{ id: tracker.id, label: tracker.label }"
                        v-bind:disabled="tracker.disabled"
                        v-bind:key="tracker.id"
                    >
                        {{ tracker.label }}
                    </option>
                </select>
                <button
                    type="button"
                    class="tlp-append tlp-button-primary tlp-button-outline"
                    v-bind:disabled="is_add_button_disabled"
                    v-on:click="addTrackerToSelection"
                    data-test="cross-tracker-selector-tracker-button"
                >
                    <i
                        aria-hidden="true"
                        class="tlp-button-icon fa-solid"
                        v-bind:class="{
                            'fa-circle-notch fa-spin': is_loader_shown,
                            'fa-plus': !is_loader_shown,
                        }"
                        data-test="tracker-loader"
                    ></i>
                    {{ $gettext("Add") }}
                </button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { getTrackersOfProject } from "../../api/rest-querier";
import type { ProjectInfo, SelectedTracker, TrackerInfo } from "../../type";
import { NOTIFY_FAULT, RETRIEVE_PROJECTS } from "../../injection-symbols";
import { ProjectIdentifier } from "../../domain/ProjectIdentifier";
import { ProjectsRetrievalFault } from "../../domain/ProjectsRetrievalFault";
import { TrackersRetrievalFault } from "../../domain/TrackersRetrievalFault";

type TrackerSelectOption = TrackerInfo & {
    readonly disabled: boolean;
};

export type AddTrackerToSelectionCommand = {
    readonly selected_project: ProjectInfo;
    readonly selected_tracker: TrackerInfo;
};

const { $gettext } = useGettext();

const props = defineProps<{ selected_trackers: ReadonlyArray<SelectedTracker> }>();
const emit = defineEmits<{
    (e: "tracker-added", add: AddTrackerToSelectionCommand): void;
}>();
const notifyFault = strictInject(NOTIFY_FAULT);
const projects_retriever = strictInject(RETRIEVE_PROJECTS);

const projects = ref<ReadonlyArray<ProjectInfo>>([]);
const trackers = ref<ReadonlyArray<TrackerInfo>>([]);
const selected_project = ref<ProjectInfo | null>(null);
const tracker_to_add = ref<TrackerInfo | null>(null);
const is_loader_shown = ref(false);

const is_project_select_disabled = computed(() => projects.value.length === 0);
const is_tracker_select_disabled = computed(() => trackers.value.length === 0);
const is_add_button_disabled = computed(() => tracker_to_add.value === null);
const tracker_options = computed<ReadonlyArray<TrackerSelectOption>>(() => {
    return trackers.value.map((tracker: TrackerInfo) => {
        const is_already_selected = props.selected_trackers.some(
            ({ tracker_id }) => tracker_id === tracker.id,
        );
        return {
            id: tracker.id,
            label: tracker.label,
            disabled: is_already_selected,
        };
    });
});

function loadProjects(): void {
    is_loader_shown.value = true;
    projects_retriever
        .getSortedProjectsIAmMemberOf()
        .match(
            (sorted_projects: ReadonlyArray<ProjectInfo>) => {
                projects.value = sorted_projects;
                selected_project.value = sorted_projects[0];
            },
            (fault) => notifyFault(ProjectsRetrievalFault(fault)),
        )
        .then(() => {
            is_loader_shown.value = false;
        });
}

function loadTrackers(project_id: ProjectIdentifier): void {
    is_loader_shown.value = true;
    getTrackersOfProject(project_id)
        .match(
            (fetched_trackers) => {
                trackers.value = fetched_trackers;
            },
            (fault) => notifyFault(TrackersRetrievalFault(fault)),
        )
        .then(() => {
            is_loader_shown.value = false;
        });
}

watch(selected_project, (new_value: ProjectInfo | null) => {
    tracker_to_add.value = null;
    trackers.value = [];
    if (new_value) {
        loadTrackers(ProjectIdentifier.fromProjectInfo(new_value));
    }
});

onMounted(() => {
    loadProjects();
});

function addTrackerToSelection(): void {
    if (!selected_project.value || !tracker_to_add.value) {
        return;
    }
    emit("tracker-added", {
        selected_project: selected_project.value,
        selected_tracker: tracker_to_add.value,
    });
    tracker_to_add.value = null;
}

defineExpose({
    selected_project,
    tracker_to_add,
    projects,
    trackers,
});
</script>

<style lang="scss" scoped>
.cross-tracker-selector {
    display: flex;
    gap: var(--tlp-x-large-spacing);
    margin: 0 0 var(--tlp-small-spacing);
}

.tlp-form-element {
    margin: 0;
}
</style>

<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <div class="tlp-table-actions">
        <project-allower
            class="tlp-table-actions-element"
            v-bind:error="setError"
            v-bind:server="server"
        />
        <button
            type="button"
            class="tlp-button-danger tlp-button tlp-table-actions-element"
            v-on:click="onDelete"
            data-test="delete"
            v-bind:disabled="projects_to_remove.length === 0"
        >
            <i class="fa-solid fa-circle-minus tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Revoke access to selected") }}
        </button>
        <div class="tlp-table-actions-spacer"></div>
        <div class="tlp-form-element tlp-table-actions-element">
            <input
                type="search"
                class="tlp-search tlp-table-actions-filter"
                data-test="filter"
                v-model="filter"
                autocomplete="off"
                v-bind:placeholder="$gettext('Project name or id')"
            />
        </div>
    </div>

    <remove-project-confirmation-modal
        v-if="show_remove_project_modal"
        v-bind:server="server"
        v-bind:nb="projects_to_remove.length"
        v-on:cancel-project-removal="show_remove_project_modal = false"
    />

    <div class="tlp-alert-danger" v-if="error_message.length > 0">
        {{ error_message }}
    </div>

    <table class="tlp-table" id="allowed-projects-list">
        <thead>
            <tr>
                <th>
                    <input
                        type="checkbox"
                        v-model="select_all"
                        v-on:change="toggleAllDelete($event.target)"
                        data-test="remove-all"
                    />
                </th>
                <th class="tlp-table-cell-numeric">
                    {{ $gettext("Id") }}
                </th>
                <th class="tlp-table-cell-main-content">
                    {{ $gettext("Project") }}
                    <i class="fa-solid fa-caret-down tlp-table-sort-icon" aria-hidden="true"></i>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="sorted_projects.length === 0">
                <td colspan="3" class="tlp-table-cell-empty">
                    {{ $gettext("No project can use this server.") }}
                </td>
            </tr>
            <tr v-else-if="filtered_projects.length === 0">
                <td colspan="3" class="tlp-table-cell-empty">
                    {{ $gettext("No project matchs your query.") }}
                </td>
            </tr>
            <tr
                v-for="project of filtered_projects"
                v-bind:key="server.id + '-' + project.id"
                v-bind:class="{
                    'tlp-table-row-danger': project.is_removed,
                }"
            >
                <td>
                    <input
                        type="checkbox"
                        name="projects-to-remove[]"
                        v-model="projects_to_remove"
                        v-bind:value="project.id"
                        v-bind:data-test="'projects-to-remove-' + project.id"
                    />
                </td>
                <td class="tlp-table-cell-numeric">
                    {{ project.id }}
                </td>
                <td>
                    <a
                        v-bind:href="project.url"
                        class="onlyoffice-admin-restrict-server-modal-project-link"
                    >
                        {{ project.label }}
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { Project, Server } from "../../../type";
import { computed, ref } from "vue";
import ProjectAllower from "./ProjectAllower.vue";
import RemoveProjectConfirmationModal from "./RemoveProjectConfirmationModal.vue";

const props = defineProps<{
    server: Server;
    submit: () => void;
}>();

interface DisplayedProject extends Project {
    readonly is_removed: boolean;
}

const error_message = ref("");
const filter = ref("");
const projects_to_remove = ref<number[]>([]);
const select_all = ref(false);
const show_remove_project_modal = ref(false);

const displayed_projects = computed(() =>
    props.server.project_restrictions.map(
        (project: Project): DisplayedProject => ({
            ...project,
            is_removed: projects_to_remove.value.includes(project.id),
        }),
    ),
);

const sorted_projects = computed((): DisplayedProject[] =>
    [...displayed_projects.value].sort((a, b) =>
        a.label.localeCompare(b.label, undefined, { numeric: true }),
    ),
);

const filtered_projects = computed((): DisplayedProject[] =>
    sorted_projects.value.filter(
        (project) =>
            filter.value === "" ||
            String(project.id).indexOf(filter.value) !== -1 ||
            project.label.toLowerCase().indexOf(filter.value.toLowerCase()) !== -1,
    ),
);

function onDelete(): void {
    show_remove_project_modal.value = true;
}

function setError(message: string): void {
    error_message.value = message;
}

function toggleAllDelete(checkbox: EventTarget | null): void {
    if (!(checkbox instanceof HTMLInputElement)) {
        return;
    }

    if (checkbox.checked) {
        projects_to_remove.value = filtered_projects.value.map((project) => project.id);
    } else {
        projects_to_remove.value = projects_to_remove.value.filter(
            (project_id) => !filtered_projects.value.find((project) => project.id === project_id),
        );
    }
}
</script>

<style lang="scss" scoped>
.tlp-table-actions {
    margin: var(--tlp-x-large-spacing) 0 var(--tlp-medium-spacing);
}

.onlyoffice-admin-restrict-server-modal-project-link {
    color: var(--tlp-typo-default-text-color);
}
</style>

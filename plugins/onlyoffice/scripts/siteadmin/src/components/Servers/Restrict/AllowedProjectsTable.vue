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
            v-bind:add="addProject"
            v-bind:error="setError"
        />
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

    <div class="tlp-alert-danger" v-if="error_message.length > 0">
        {{ error_message }}
    </div>

    <table class="tlp-table" id="allowed-projects-list">
        <thead>
            <tr>
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
                <td colspan="2" class="tlp-table-cell-empty">
                    {{ $gettext("No project can use this server.") }}
                </td>
            </tr>
            <tr v-for="project of filtered_projects" v-bind:key="server.id + '-' + project.id">
                <td class="tlp-table-cell-numeric">
                    {{ project.id }}
                </td>
                <td>
                    <a v-bind:href="project.url">{{ project.label }}</a>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { Project, Server } from "../../../type";
import { computed, ref } from "vue";
import ProjectAllower from "./ProjectAllower.vue";

const props = defineProps<{ server: Server }>();

const added_projects = ref<Project[]>([]);
const error_message = ref("");
const filter = ref("");

const sorted_projects = computed(
    (): ReadonlyArray<Project> =>
        [...props.server.project_restrictions, ...added_projects.value]
            .filter(is_a_duplicate)
            .sort((a, b) => a.label.localeCompare(b.label, undefined, { numeric: true }))
);

const filtered_projects = computed(
    (): ReadonlyArray<Project> =>
        [...sorted_projects.value].filter(
            (project) =>
                filter.value === "" ||
                String(project.id).indexOf(filter.value) !== -1 ||
                project.label.toLowerCase().indexOf(filter.value.toLowerCase()) !== -1
        )
);

function addProject(project: Project): void {
    added_projects.value.push(project);
}

function setError(message: string): void {
    error_message.value = message;
}

function is_a_duplicate(current: Project, index: number, projects: Project[]): boolean {
    return index === projects.findIndex((sibling) => sibling.id === current.id);
}
</script>

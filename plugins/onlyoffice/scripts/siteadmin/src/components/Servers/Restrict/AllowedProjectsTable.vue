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
                <th class="tlp-table-cell-actions"></th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="sorted_projects.length === 0">
                <td colspan="3" class="tlp-table-cell-empty">
                    {{ $gettext("No project can use this server.") }}
                </td>
            </tr>
            <tr
                v-for="project of filtered_projects"
                v-bind:key="server.id + '-' + project.id"
                v-bind:class="{
                    'tlp-table-row-success': project.is_added,
                    'tlp-table-row-danger': project.is_removed,
                }"
            >
                <td>
                    <input
                        type="checkbox"
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
                <td class="tlp-table-cell-actions">
                    <span
                        class="tlp-badge-warning tlp-badge-outline"
                        v-if="project.is_added && project.already_allowed_for_other_server"
                        v-bind:title="
                            $gettext('This project is currently allowed for %{ server }', {
                                server: project.already_allowed_for_other_server.server_url,
                            })
                        "
                        data-test="badge-warning-currently-allowed"
                    >
                        {{ $gettext("To be revoked from another server") }}
                    </span>
                    <span class="tlp-badge-success tlp-badge-outline" v-if="project.is_added">
                        {{ $gettext("To be allowed") }}
                    </span>
                    <span class="tlp-badge-danger tlp-badge-outline" v-if="project.is_removed">
                        {{ $gettext("To be revoked") }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    <template v-for="project of deduplicated_projects">
        <input
            v-bind:key="server.id + '-' + project.id + '-hidden-input'"
            type="hidden"
            name="projects[]"
            v-bind:value="project.id"
            v-if="!project.is_removed"
            v-bind:data-test="'project-id-to-restrict-' + project.id"
        />
    </template>
</template>

<script setup lang="ts">
import type { Project, Server } from "../../../type";
import { computed, ref } from "vue";
import ProjectAllower from "./ProjectAllower.vue";
import { CONFIG } from "../../../injection-keys";
import { strictInject } from "../../../helpers/strict-inject";

const config = strictInject(CONFIG);

const props = defineProps<{
    server: Server;
    set_nb_to_allow: (nb: number) => void;
    set_nb_to_revoke: (nb: number) => void;
    set_nb_to_move: (nb: number) => void;
}>();

interface DisplayedProject extends Project {
    readonly is_added: boolean;
    readonly already_allowed_for_other_server: Server | undefined;
    is_removed: boolean;
}

const existing_projects = ref<DisplayedProject[]>(
    props.server.project_restrictions.map(
        (project): DisplayedProject => ({
            ...project,
            is_added: false,
            is_removed: false,
            already_allowed_for_other_server: undefined,
        })
    )
);
const added_projects = ref<DisplayedProject[]>([]);
const error_message = ref("");
const filter = ref("");
const projects_to_remove = ref<number[]>([]);
const select_all = ref(false);

const deduplicated_projects = computed((): DisplayedProject[] =>
    [...existing_projects.value, ...added_projects.value].filter(is_a_duplicate)
);

const sorted_projects = computed((): DisplayedProject[] =>
    [...deduplicated_projects.value].sort((a, b) =>
        a.label.localeCompare(b.label, undefined, { numeric: true })
    )
);

const filtered_projects = computed((): DisplayedProject[] =>
    sorted_projects.value.filter(
        (project) =>
            filter.value === "" ||
            String(project.id).indexOf(filter.value) !== -1 ||
            project.label.toLowerCase().indexOf(filter.value.toLowerCase()) !== -1
    )
);

function addProject(project: Project): void {
    added_projects.value.push({
        ...project,
        is_added: true,
        is_removed: false,
        already_allowed_for_other_server: config.servers.find(
            (server) =>
                server.id !== props.server.id &&
                server.project_restrictions.some(
                    (already_allowed_project) => project.id === already_allowed_project.id
                )
        ),
    });
    props.set_nb_to_allow(deduplicated_projects.value.filter((project) => project.is_added).length);
    props.set_nb_to_move(
        deduplicated_projects.value.filter(
            (project) => project.is_added && project.already_allowed_for_other_server
        ).length
    );
}

function setError(message: string): void {
    error_message.value = message;
}

function onDelete(): void {
    projects_to_remove.value.forEach((id) => {
        const existing_project = existing_projects.value.find((project) => project.id === id);
        if (existing_project) {
            existing_project.is_removed = true;
        }

        added_projects.value = added_projects.value.filter((project) => project.id !== id);
    });
    projects_to_remove.value = [];
    select_all.value = false;

    props.set_nb_to_allow(deduplicated_projects.value.filter((project) => project.is_added).length);
    props.set_nb_to_revoke(existing_projects.value.filter((project) => project.is_removed).length);
}

function toggleAllDelete(checkbox: EventTarget | null): void {
    if (!(checkbox instanceof HTMLInputElement)) {
        return;
    }

    if (checkbox.checked) {
        projects_to_remove.value = deduplicated_projects.value.map((project) => project.id);
    } else {
        projects_to_remove.value = [];
    }
}

function is_a_duplicate(
    current: DisplayedProject,
    index: number,
    projects: DisplayedProject[]
): boolean {
    return index === projects.findIndex((sibling) => sibling.id === current.id);
}
</script>

<style lang="scss" scoped>
.tlp-table-actions {
    margin: var(--tlp-x-large-spacing) 0 var(--tlp-medium-spacing);
}

.onlyoffice-admin-restrict-server-modal-project-link {
    color: var(--tlp-typo-default-text-color);
}

.tlp-table-cell-actions > .tlp-badge-outline + .tlp-badge-outline {
    margin: 0 0 0 var(--tlp-small-spacing);
}
</style>

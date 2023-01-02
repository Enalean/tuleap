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
        <div class="tlp-form-element-append tlp-table-actions-element">
            <project-autocompleter
                class="tlp-select onlyoffice-admin-restrict-server-select-project"
            />
            <button type="button" id="allow-project" class="tlp-append tlp-button-primary" disabled>
                <i class="fa-solid fa-circle-check tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Allow project") }}
            </button>
        </div>
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
            <tr v-for="project of sorted_projects" v-bind:key="server.id + '-' + project.id">
                <td class="tlp-table-cell-numeric">{{ project.id }}</td>
                <td>
                    <a v-bind:href="project.url">{{ project.label }}</a>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { Project, Server } from "../../../type";
import ProjectAutocompleter from "./ProjectAutocompleter.vue";
import { computed } from "vue";

const props = defineProps<{ server: Server }>();

const sorted_projects = computed(
    (): ReadonlyArray<Project> =>
        [...props.server.project_restrictions].sort((a, b) =>
            a.label.localeCompare(b.label, undefined, { numeric: true })
        )
);
</script>

<style lang="scss">
.onlyoffice-admin-restrict-server-select-project {
    width: 200px;
}
</style>

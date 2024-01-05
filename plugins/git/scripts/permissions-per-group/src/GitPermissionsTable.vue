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
    <table class="tlp-table permission-per-group-table" id="permission-per-group-git-repositories">
        <thead>
            <tr class="permission-per-group-sextuple-column-table">
                <th>{{ $gettext("Repository") }}</th>
                <th>{{ $gettext("Branch") }}</th>
                <th>{{ $gettext("Tag") }}</th>
                <th>{{ $gettext("Readers") }}</th>
                <th>{{ $gettext("Writers") }}</th>
                <th>{{ $gettext("Rewinders") }}</th>
            </tr>
        </thead>

        <tbody v-if="is_empty_state_shown" data-test="git-permission-table-empty-state">
            <tr>
                <td colspan="6" class="tlp-table-cell-empty">
                    {{ empty_state }}
                </td>
            </tr>
        </tbody>

        <git-permissions-table-repository
            v-for="repository in repositories"
            v-bind:key="repository.repository_id"
            v-bind:data-test="'git-permissions-table-repository-' + repository.repository_id"
            v-bind:repository="repository"
            v-bind:filter="filter"
            v-on:filtered="togglePermission"
        />
    </table>
</template>

<script setup lang="ts">
import GitPermissionsTableRepository from "./GitPermissionsTableRepository.vue";
import { computed, ref, watch } from "vue";
import type { RepositoryFineGrainedPermissions, RepositorySimplePermissions } from "./type";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    repositories: (RepositoryFineGrainedPermissions | RepositorySimplePermissions)[];
    selected_ugroup_name: string;
    filter: string;
}>();

const { $gettext, interpolate } = useGettext();

const nb_repo_hidden = ref(0);

const no_repo_empty_state = computed(() => {
    return $gettext("No repository found for project");
});
const filter_empty_state = computed(() => {
    return $gettext("There isn't any matching repository");
});
const ugroup_empty_state = computed(() => {
    return interpolate(
        $gettext("%{ user_group } has no permission for any repository in this project"),
        { user_group: props.selected_ugroup_name },
    );
});
const is_empty = computed(() => {
    return props.repositories.length === 0;
});
const has_a_selected_ugroup = computed(() => {
    return props.selected_ugroup_name !== "";
});
const are_all_repositories_hidden = computed(() => {
    return !is_empty.value && nb_repo_hidden.value === props.repositories.length;
});
const is_empty_state_shown = computed(() => {
    return is_empty.value || are_all_repositories_hidden.value;
});
const empty_state = computed(() => {
    return are_all_repositories_hidden.value
        ? filter_empty_state.value
        : has_a_selected_ugroup.value
          ? ugroup_empty_state.value
          : no_repo_empty_state.value;
});

watch(
    () => props.filter,
    () => {
        nb_repo_hidden.value = 0;
    },
);

function togglePermission(event: { hidden: boolean }): void {
    if (event.hidden) {
        nb_repo_hidden.value++;
    }
}
</script>

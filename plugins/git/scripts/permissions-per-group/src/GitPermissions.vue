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
    <section class="tlp-pane-section">
        <div class="tlp-alert-danger" v-if="has_error" data-test="git-permission-error">
            {{ error }}
        </div>

        <div class="permission-per-group-load-button" v-if="display_button_load_all">
            <button
                data-test="git-permission-button-load"
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadAll()"
            >
                {{ $gettext("See all repositories permissions") }}
            </button>
        </div>

        <div
            data-test="git-permission-loading"
            class="permission-per-group-loader"
            v-if="is_loading"
        ></div>

        <h2 class="tlp-pane-subtitle" v-if="is_loaded">{{ $gettext("Repository permissions") }}</h2>
        <git-inline-filter v-if="is_loaded" v-model="filter" />
        <git-permissions-table
            v-if="is_loaded"
            v-bind:repositories="repositories"
            v-bind:selected_ugroup_name="selected_ugroup_name"
            v-bind:filter="filter"
        />
    </section>
</template>

<script setup lang="ts">
import { getGitPermissions } from "./rest-querier";
import { computed, ref } from "vue";
import GitInlineFilter from "./GitInlineFilter.vue";
import GitPermissionsTable from "./GitPermissionsTable.vue";
import type { RepositoryFineGrainedPermissions, RepositorySimplePermissions } from "./type";
import { useGettext } from "vue3-gettext";
import type { Fault } from "@tuleap/fault";

const props = defineProps<{
    selected_project_id: number;
    selected_ugroup_id: string;
    selected_ugroup_name: string;
}>();

const repositories = ref<(RepositoryFineGrainedPermissions | RepositorySimplePermissions)[]>([]);
const error = ref<string | null>(null);
const is_loaded = ref(false);
const is_loading = ref(false);
const has_error = computed(() => error.value !== null);
const display_button_load_all = computed(() => !is_loaded.value && !is_loading.value);
const filter = ref("");

const { $gettext } = useGettext();

function loadAll(): void {
    is_loading.value = true;
    getGitPermissions(props.selected_project_id, props.selected_ugroup_id).match(
        (git_permissions: { repositories: RepositoryFineGrainedPermissions[] }) => {
            is_loaded.value = true;
            is_loading.value = false;
            repositories.value = git_permissions.repositories;
        },
        (fault: Fault) => {
            is_loading.value = false;
            error.value = String(fault);
        },
    );
}
</script>

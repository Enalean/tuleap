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
        <div class="tlp-alert-danger" v-if="has_error">
            {{ error }}
        </div>

        <div class="permission-per-group-load-button" v-if="display_button_load_all">
            <button class="tlp-button-primary tlp-button-outline" v-on:click="loadAll()">
                {{ $gettext("See all repositories") }}
            </button>
        </div>

        <div class="permission-per-group-loader" v-if="is_loading"></div>

        <h2 class="tlp-pane-subtitle" v-if="is_loaded">
            {{ $gettext("Repositories permissions") }}
        </h2>
        <table class="tlp-table" v-if="is_loaded">
            <thead>
                <tr>
                    <th class="svn-permission-per-group-repository">
                        {{ $gettext("Repository") }}
                    </th>
                </tr>
            </thead>
            <tbody v-if="!is_empty" key="not-empty">
                <tr v-for="permission in permissions" v-bind:key="permission.name">
                    <td>
                        <a v-bind:href="permission.url">
                            {{ permission.name }}
                        </a>
                    </td>
                </tr>
            </tbody>
            <tbody v-else key="empty">
                <tr>
                    <td class="tlp-table-cell-empty">
                        {{ $gettext("No repository found for project") }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup lang="ts">
import type { RepositoriesPermissions } from "./rest-querier";
import { getSVNPermissions } from "./rest-querier";
import { computed, ref } from "vue";

const props = defineProps<{
    project_id: string;
}>();

const permissions = ref<RepositoriesPermissions>([]);
const error = ref<string | null>(null);
const is_loaded = ref(false);
const is_loading = ref(false);

const is_empty = computed(() => permissions.value.length === 0);
const has_error = computed(() => error.value !== null);
const display_button_load_all = computed(() => !is_loaded.value && !is_loading.value);

function loadAll(): void {
    is_loading.value = true;
    getSVNPermissions(props.project_id).match(
        ({
            repositories_representation,
        }: {
            repositories_representation: RepositoriesPermissions;
        }) => {
            is_loaded.value = true;
            is_loading.value = false;
            permissions.value = repositories_representation;
        },
        (fault) => {
            is_loading.value = false;
            error.value = String(fault);
        },
    );
}
</script>

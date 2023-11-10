<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
                {{ $gettext("See all plannings permissions") }}
            </button>
        </div>

        <div class="permission-per-group-loader" v-if="is_loading"></div>

        <table class="tlp-table permission-per-group-table" v-if="is_loaded">
            <thead>
                <tr class="permission-per-group-double-column-table">
                    <th>{{ $gettext("Planning") }}</th>
                    <th>{{ $gettext("Who can prioritize?") }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="permission in permissions" v-bind:key="permission.name">
                    <td>
                        <a v-bind:href="permission.quick_link">{{ permission.name }}</a>
                    </td>
                    <td>
                        <agile-dashboard-permissions-badge
                            v-for="group in permission.ugroups"
                            v-bind:key="group.ugroup_name"
                            v-bind:is_project_admin="group.is_project_admin"
                            v-bind:is_static="group.is_static"
                            v-bind:is_custom="group.is_custom"
                            v-bind:group_name="group.ugroup_name"
                        />
                    </td>
                </tr>
            </tbody>
            <tbody v-if="is_empty">
                <tr>
                    <td
                        v-if="has_a_selected_u_group"
                        key="selected-ugroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                    >
                        {{ no_perms_label }}
                    </td>
                    <td v-else key="no-selected-ugroup" colspan="2" class="tlp-table-cell-empty">
                        {{ $gettext("Agiledashboard has no planning defined") }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script setup lang="ts">
import type { PlanningsPermissions } from "./rest-querier.js";
import { getAgiledashboardPermissions } from "./rest-querier.js";
import AgileDashboardPermissionsBadge from "@tuleap/vue3-permissions-per-group-badge";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    selected_project_id: string;
    selected_ugroup_id: string;
    selected_ugroup_name: string;
}>();

const permissions = ref<PlanningsPermissions>([]);
const error = ref<string | null>(null);
const is_loaded = ref(false);
const is_loading = ref(false);

const is_empty = computed(() => permissions.value.length === 0);
const has_error = computed(() => error.value !== null);
const display_button_load_all = computed(() => !is_loaded.value && !is_loading.value);
const has_a_selected_u_group = computed(() => props.selected_ugroup_name !== "");

const { interpolate, $gettext } = useGettext();
const no_perms_label = computed(() =>
    interpolate($gettext("%{ user_group } has no permission for agiledashboard plannings"), {
        user_group: props.selected_ugroup_name,
    }),
);

function loadAll(): void {
    is_loading.value = true;
    getAgiledashboardPermissions(props.selected_project_id, props.selected_ugroup_id).match(
        ({ plannings_permissions }: { plannings_permissions: PlanningsPermissions }) => {
            is_loaded.value = true;
            is_loading.value = false;
            permissions.value = plannings_permissions;
        },
        (fault) => {
            is_loading.value = false;
            error.value = String(fault);
        },
    );
}
</script>

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
        <div class="tlp-alert-danger" v-if="hasError">
            {{ error }}
        </div>

        <div class="permission-per-group-load-button" v-if="displayButtonLoadAll">
            <button
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadAll()"
                v-translate
            >
                See all plannings permissions
            </button>
        </div>

        <div class="permission-per-group-loader" v-if="is_loading"></div>

        <table class="tlp-table permission-per-group-table" v-if="is_loaded">
            <thead>
                <tr class="permission-per-group-double-column-table">
                    <th v-translate>Planning</th>
                    <th v-translate>Who can prioritize?</th>
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
                            v-bind:is-project-admin="group.is_project_admin"
                            v-bind:is-static="group.is_static"
                            v-bind:is-custom="group.is_custom"
                            v-bind:group-name="group.ugroup_name"
                        />
                    </td>
                </tr>
            </tbody>
            <tbody v-if="isEmpty">
                <tr>
                    <td
                        v-if="has_a_selected_u_group"
                        key="selected-ugroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                        v-translate="{ user_group: selectedUgroupName }"
                    >
                        %{ user_group } has no permission for agiledashboard plannings
                    </td>
                    <td
                        v-else
                        key="no-selected-ugroup"
                        colspan="2"
                        class="tlp-table-cell-empty"
                        v-translate
                    >
                        Agiledashboard has no planning defined
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script>
import { getAgiledashboardPermissions } from "./rest-querier.js";
import AgileDashboardPermissionsBadge from "@tuleap/vue-permissions-per-group-badge";

export default {
    name: "AgileDashboardPermissions",
    components: { AgileDashboardPermissionsBadge },
    props: {
        selectedUgroupId: String,
        selectedProjectId: String,
        selectedUgroupName: String,
    },
    data() {
        return {
            is_loaded: false,
            is_loading: false,
            permissions: [],
            error: null,
        };
    },
    computed: {
        isEmpty() {
            return this.permissions.length === 0;
        },
        hasError() {
            return this.error !== null;
        },
        displayButtonLoadAll() {
            return !this.is_loaded && !this.is_loading;
        },
        has_a_selected_u_group() {
            return this.selectedUgroupName !== "";
        },
    },
    methods: {
        async loadAll() {
            try {
                this.is_loading = true;
                const { plannings_permissions } = await getAgiledashboardPermissions(
                    this.selectedProjectId,
                    this.selectedUgroupId,
                );
                this.is_loaded = true;
                this.permissions = plannings_permissions;
            } catch (e) {
                const { error } = await e.response.json();
                this.error = error;
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>

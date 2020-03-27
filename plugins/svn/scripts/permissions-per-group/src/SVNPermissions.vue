<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
                See all repositories
            </button>
        </div>

        <div class="permission-per-group-loader" v-if="is_loading"></div>

        <h2 class="tlp-pane-subtitle" v-if="is_loaded" v-translate>Repositories permissions</h2>
        <table class="tlp-table" v-if="is_loaded">
            <thead>
                <tr>
                    <th class="svn-permission-per-group-repository" v-translate>Repository</th>
                </tr>
            </thead>
            <tbody v-if="!isEmpty" key="not-empty">
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
                    <td class="tlp-table-cell-empty" v-translate>
                        No repository found for project
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</template>

<script>
import { getSVNPermissions } from "./rest-querier.js";

export default {
    name: "SVNPermissions",
    props: {
        projectId: String,
    },
    data() {
        return {
            is_loading: false,
            is_loaded: false,
            permissions: [],
            error: null,
        };
    },
    computed: {
        displayButtonLoadAll() {
            return !this.is_loaded && !this.is_loading;
        },
        isEmpty() {
            return this.permissions.length === 0;
        },
        hasError() {
            return this.error !== null;
        },
    },
    methods: {
        async loadAll() {
            try {
                this.is_loading = true;
                const { repositories_representation } = await getSVNPermissions(this.projectId);

                this.permissions = repositories_representation;
                this.is_loaded = true;
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

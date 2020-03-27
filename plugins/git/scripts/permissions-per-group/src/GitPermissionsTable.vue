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
    <table class="tlp-table permission-per-group-table" id="permission-per-group-git-repositories">
        <thead>
            <tr class="permission-per-group-sextuple-column-table">
                <th v-translate>Repository</th>
                <th v-translate>Branch</th>
                <th v-translate>Tag</th>
                <th v-translate>Readers</th>
                <th v-translate>Writers</th>
                <th v-translate>Rewinders</th>
            </tr>
        </thead>

        <tbody v-if="is_empty_state_shown">
            <tr>
                <td colspan="6" class="tlp-table-cell-empty">
                    {{ empty_state }}
                </td>
            </tr>
        </tbody>

        <git-permissions-table-repository
            v-for="repository in repositories"
            v-bind:key="repository.repository_id"
            v-bind:repository="repository"
            v-bind:filter="filter"
            v-on:filtered="togglePermission"
        />
    </table>
</template>

<script>
import GitPermissionsTableRepository from "./GitPermissionsTableRepository.vue";
import { sprintf } from "sprintf-js";

export default {
    name: "GitPermissionsTable",
    components: {
        GitPermissionsTableRepository,
    },
    props: {
        repositories: Array,
        selectedUgroupName: String,
        filter: String,
    },
    data() {
        return {
            nb_repo_hidden: 0,
        };
    },
    computed: {
        no_repo_empty_state() {
            return this.$gettext("No repository found for project");
        },
        filter_empty_state() {
            return this.$gettext("There isn't any matching repository");
        },
        ugroup_empty_state() {
            return sprintf(
                this.$gettext("%s has no permission for any repository in this project"),
                this.selectedUgroupName
            );
        },
        is_empty() {
            return this.repositories.length === 0;
        },
        has_a_selected_ugroup() {
            return this.selectedUgroupName !== "";
        },
        are_all_repositories_hidden() {
            return !this.is_empty && this.nb_repo_hidden === this.repositories.length;
        },
        is_empty_state_shown() {
            return this.is_empty || this.are_all_repositories_hidden;
        },
        empty_state() {
            return this.are_all_repositories_hidden
                ? this.filter_empty_state
                : this.has_a_selected_ugroup
                ? this.ugroup_empty_state
                : this.no_repo_empty_state;
        },
    },
    watch: {
        filter() {
            this.nb_repo_hidden = 0;
        },
    },
    methods: {
        togglePermission(event) {
            if (event.hidden) {
                this.nb_repo_hidden++;
            }
        },
    },
};
</script>

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
                <th v-translate>Repository</th>
                <th v-translate>Branch</th>
                <th v-translate>Tag</th>
                <th v-translate>Readers</th>
                <th v-translate>Writers</th>
                <th v-translate>Rewinders</th>
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
            v-bind:repository="repository"
            v-bind:filter="filter"
            v-on:filtered="togglePermission"
        />
    </table>
</template>

<script lang="ts">
import GitPermissionsTableRepository from "./GitPermissionsTableRepository.vue";
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { RepositoryFineGrainedPermissions, RepositorySimplePermissions } from "./type";

@Component({
    components: { GitPermissionsTableRepository },
})
export default class GitPermissionsTable extends Vue {
    @Prop()
    readonly repositories!: (RepositoryFineGrainedPermissions | RepositorySimplePermissions)[];
    @Prop()
    readonly selectedUgroupName!: string;
    @Prop()
    readonly filter!: string;

    nb_repo_hidden = 0;

    get no_repo_empty_state(): string {
        return this.$gettext("No repository found for project");
    }
    get filter_empty_state(): string {
        return this.$gettext("There isn't any matching repository");
    }
    get ugroup_empty_state(): string {
        return this.$gettextInterpolate(
            this.$gettext("%{ user_group } has no permission for any repository in this project"),
            { user_group: this.selectedUgroupName },
        );
    }
    get is_empty(): boolean {
        return this.repositories.length === 0;
    }
    get has_a_selected_ugroup(): boolean {
        return this.selectedUgroupName !== "";
    }
    get are_all_repositories_hidden(): boolean {
        return !this.is_empty && this.nb_repo_hidden === this.repositories.length;
    }
    get is_empty_state_shown(): boolean {
        return this.is_empty || this.are_all_repositories_hidden;
    }
    get empty_state(): string {
        return this.are_all_repositories_hidden
            ? this.filter_empty_state
            : this.has_a_selected_ugroup
              ? this.ugroup_empty_state
              : this.no_repo_empty_state;
    }

    @Watch("filter")
    reset_hidden_repo() {
        this.nb_repo_hidden = 0;
    }

    togglePermission(event: { hidden: boolean }) {
        if (event.hidden) {
            this.nb_repo_hidden++;
        }
    }
}
</script>

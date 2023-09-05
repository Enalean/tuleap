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
        <div class="tlp-alert-danger" v-if="hasError" data-test="git-permission-error">
            {{ error }}
        </div>

        <div class="permission-per-group-load-button" v-if="displayButtonLoadAll">
            <button
                data-test="git-permission-button-load"
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadAll()"
                v-translate
            >
                See all repositories permissions
            </button>
        </div>

        <div
            data-test="git-permission-loading"
            class="permission-per-group-loader"
            v-if="is_loading"
        ></div>

        <h2 class="tlp-pane-subtitle" v-if="is_loaded" v-translate>Repository permissions</h2>
        <git-inline-filter v-if="is_loaded" v-model="filter" />
        <git-permissions-table
            v-if="is_loaded"
            v-bind:repositories="repositories"
            v-bind:selected-ugroup-name="selectedUgroupName"
            v-bind:filter="filter"
        />
    </section>
</template>

<script lang="ts">
import { getGitPermissions } from "./rest-querier";
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import GitInlineFilter from "./GitInlineFilter.vue";
import GitPermissionsTable from "./GitPermissionsTable.vue";
import type { RepositoryFineGrainedPermissions, RepositorySimplePermissions } from "./type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

@Component({
    components: { GitInlineFilter, GitPermissionsTable },
})
export default class GitPermissions extends Vue {
    @Prop()
    readonly selectedUgroupId!: string;
    @Prop()
    readonly selectedProjectId!: number;
    @Prop()
    readonly selectedUgroupName!: string;

    is_loaded = false;
    is_loading = false;
    repositories: (RepositoryFineGrainedPermissions | RepositorySimplePermissions)[] = [];
    error = null;
    filter = "";

    get hasError(): boolean {
        return this.error !== null;
    }

    get displayButtonLoadAll(): boolean {
        return !this.is_loaded && !this.is_loading;
    }

    async loadAll(): Promise<void> {
        try {
            this.is_loading = true;
            const { repositories } = await getGitPermissions(
                this.selectedProjectId,
                this.selectedUgroupId,
            );
            this.is_loaded = true;
            this.repositories = repositories;
        } catch (e) {
            if (e instanceof FetchWrapperError) {
                const { error } = await e.response.json();
                this.error = error;
            }
        } finally {
            this.is_loading = false;
        }
    }
}
</script>

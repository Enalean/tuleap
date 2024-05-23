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
    <div v-if="are_there_personal_repositories()" class="git-repository-actions-select-owners">
        <label class="tlp-label" for="git-repository-select-owner">
            {{ $gettext("Forks:") }}
        </label>
        <select
            id="git-repository-select-owner"
            class="tlp-select tlp-select-adjusted"
            v-model="owner_id"
            v-bind:disabled="isLoading"
        >
            <option v-bind:value="project_key()">{{ $gettext("Project repositories") }}</option>
            <optgroup v-bind:label="$gettext('Users forks')">
                <option
                    v-for="owner in sorted_repositories_owners()"
                    v-bind:key="owner.id"
                    v-bind:value="owner.id"
                >
                    {{ owner.display_name }}
                </option>
            </optgroup>
        </select>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import type { RepositoryOwner } from "../../type";
import { Getter, State } from "vuex-class";
import { Component, Watch } from "vue-property-decorator";
import { getRepositoriesOwners } from "../../repository-list-presenter";
import { PROJECT_KEY } from "../../constants";

@Component
export default class SelectOwner extends Vue {
    @Getter
    readonly isLoading!: boolean;

    @State
    readonly selected_owner_id!: string | number;

    owner_id: string | number | null = null;

    are_there_personal_repositories(): boolean {
        return getRepositoriesOwners().length > 0;
    }
    sorted_repositories_owners(): Array<RepositoryOwner> {
        return getRepositoriesOwners().sort(function (user_a, user_b) {
            return user_a.display_name.localeCompare(user_b.display_name);
        });
    }
    project_key(): string {
        return PROJECT_KEY;
    }

    mounted(): void {
        this.owner_id = this.selected_owner_id;
    }

    @Watch("owner_id")
    public updateSelectedOwnerId(value: string) {
        this.$store.dispatch("changeRepositories", value);
    }
}
</script>

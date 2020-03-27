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
    <div v-if="are_there_personal_repositories" class="git-repository-actions-select-owners">
        <label class="tlp-label" for="git-repository-select-owner">
            <translate>Forks:</translate>
        </label>
        <select
            id="git-repository-select-owner"
            class="tlp-select tlp-select-adjusted"
            v-model="selected_owner_id"
            v-bind:disabled="isLoading"
        >
            <option v-bind:value="project_key">{{ project_repositories_label }}</option>
            <optgroup v-bind:label="users_forks_label">
                <option
                    v-for="owner in sorted_repositories_owners"
                    v-bind:key="owner.id"
                    v-bind:value="owner.id"
                >
                    {{ owner.display_name }}
                </option>
            </optgroup>
        </select>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
import { getRepositoriesOwners } from "../../repository-list-presenter.js";
import { PROJECT_KEY } from "../../constants";

export default {
    name: "SelectOwner",
    computed: {
        project_repositories_label() {
            return this.$gettext("Project repositories");
        },
        users_forks_label() {
            return this.$gettext("Users forks");
        },
        are_there_personal_repositories() {
            return getRepositoriesOwners().length > 0;
        },
        sorted_repositories_owners() {
            return getRepositoriesOwners().sort(function (user_a, user_b) {
                return user_a.display_name.localeCompare(user_b.display_name);
            });
        },
        selected_owner_id: {
            get() {
                return this.$store.state.selected_owner_id;
            },
            set(value) {
                this.$store.dispatch("changeRepositories", value);
            },
        },
        project_key() {
            return PROJECT_KEY;
        },
        ...mapGetters(["isLoading"]),
    },
};
</script>

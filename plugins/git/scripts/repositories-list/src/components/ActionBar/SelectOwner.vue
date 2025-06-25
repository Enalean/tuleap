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
    <div v-if="are_there_personal_repositories" class="git-repository-actions-select-owners">
        <label class="tlp-label" for="git-repository-select-owner">
            {{ $gettext("Forks:") }}
        </label>
        <select
            id="git-repository-select-owner"
            class="tlp-select tlp-select-adjusted"
            v-model="owner_id"
            v-bind:disabled="isLoading"
            data-test="select-fork-of-user"
        >
            <option v-bind:value="PROJECT_KEY">
                {{ $gettext("Project repositories") }}
            </option>
            <optgroup v-bind:label="$gettext('Users forks')">
                <option
                    v-for="owner in repositories_owners"
                    v-bind:key="owner.id"
                    v-bind:value="owner.id"
                >
                    {{ owner.display_name }}
                </option>
            </optgroup>
        </select>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import type { RepositoryOwner } from "../../type";
import { getRepositoriesOwners } from "../../repository-list-presenter";
import { PROJECT_KEY } from "../../constants";
import { useActions, useGetters, useState } from "vuex-composition-helpers";

const { isLoading } = useGetters(["isLoading"]);
const { selected_owner_id } = useState(["selected_owner_id"]);
const { changeRepositories } = useActions(["changeRepositories"]);

const owner_id = ref<string | number | null>(null);

const are_there_personal_repositories = computed((): boolean => {
    return getRepositoriesOwners().length > 0;
});

const repositories_owners = computed((): ReadonlyArray<RepositoryOwner> => {
    return getRepositoriesOwners();
});

onMounted(() => {
    owner_id.value = selected_owner_id.value;
});

watch(owner_id, (new_value: string | number | null) => {
    changeRepositories(new_value);
});
</script>

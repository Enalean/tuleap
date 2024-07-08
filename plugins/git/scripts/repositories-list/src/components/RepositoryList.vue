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
    <div class="git-repository-list" v-if="!is_loading_initial">
        <git-repository
            v-for="repository in getFilteredRepositoriesByLastUpdateDate"
            v-bind:repository="repository"
            v-bind:key="getKey(repository)"
        />
    </div>
</template>
<script setup lang="ts">
import GitRepository from "./GitRepository.vue";
import type { Folder, FormattedGitLabRepository, Repository } from "../type";
import { useGetters, useState } from "vuex-composition-helpers";

const { is_loading_initial } = useState(["is_loading_initial"]);
const { getFilteredRepositoriesByLastUpdateDate } = useGetters([
    "getFilteredRepositoriesByLastUpdateDate",
]);

function getKey(item: Repository | Folder | FormattedGitLabRepository): string {
    if ("is_folder" in item) {
        return item.normalized_path ?? "";
    }
    return String(item.id);
}
</script>

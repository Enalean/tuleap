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
    <div class="git-repository-list" v-if="!is_loading_initial">
        <git-repository
            v-for="repository in filteredRepositoriesByLastUpdateDate"
            v-bind:repository="repository"
            v-bind:key="repository.id"
        />
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import GitRepository from "./GitRepository.vue";

export default {
    name: "RepositoryList",
    components: { GitRepository },
    computed: {
        ...mapState(["is_loading_initial"]),
        ...mapGetters(["filteredRepositoriesByLastUpdateDate"]),
    },
};
</script>

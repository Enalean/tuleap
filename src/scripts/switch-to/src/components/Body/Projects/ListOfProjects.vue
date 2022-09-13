<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div class="switch-to-projects-container" v-if="should_be_displayed">
        <template v-if="has_projects">
            <h2
                class="tlp-modal-subtitle switch-to-modal-body-title"
                id="switch-to-modal-projects-title"
                v-translate
            >
                My projects
            </h2>
            <nav
                class="switch-to-projects"
                aria-labelledby="switch-to-modal-projects-title"
                v-if="has_filtered_projects"
            >
                <project-link
                    v-for="project of filtered_projects"
                    v-bind:key="project.project_uri"
                    v-bind:project="project"
                />
            </nav>
            <trove-cat-link
                class="switch-to-projects-softwaremap"
                v-if="should_softwaremap_link_be_displayed"
            >
                {{ $gettext("Browse all…") }}
            </trove-cat-link>
        </template>
        <projects-empty-state v-else />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useSwitchToStore } from "../../../stores";
import { storeToRefs } from "pinia";
import ProjectsEmptyState from "./ProjectsEmptyState.vue";
import ProjectLink from "./ProjectLink.vue";
import TroveCatLink from "../TroveCatLink.vue";

const store = useSwitchToStore();
const { projects, filtered_projects, is_in_search_mode } = storeToRefs(store);

const has_projects = computed((): boolean => {
    return projects.value.length > 0;
});

const has_filtered_projects = computed((): boolean => {
    return filtered_projects.value.length > 0;
});

const should_be_displayed = computed((): boolean => {
    return is_in_search_mode.value === false || has_filtered_projects.value;
});

const should_softwaremap_link_be_displayed = computed((): boolean => {
    return is_in_search_mode.value === false;
});
</script>

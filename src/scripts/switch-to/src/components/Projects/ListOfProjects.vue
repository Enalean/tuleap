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
    <div class="switch-to-projects-container">
        <template v-if="has_projects">
            <h2 class="tlp-modal-subtitle switch-to-modal-body-title" v-translate>
                Working projects
            </h2>
            <nav class="switch-to-projects" v-if="has_filtered_projects">
                <project-link
                    v-for="project of filtered_projects"
                    v-bind:key="project.project_uri"
                    v-bind:project="project"
                />
            </nav>
            <p class="switch-to-modal-no-matching-projects" v-else>
                <translate>You don't belong to any projects matching your query.</translate>
            </p>
            <a
                v-if="is_trove_cat_enabled"
                href="/softwaremap/trove_list.php"
                class="switch-to-projects-softwaremap"
                data-test="trove-cat-link"
            >
                {{ trove_cat_label }}
            </a>
        </template>
        <projects-empty-state v-else />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import ProjectLink from "./ProjectLink.vue";
import { Getter, State } from "vuex-class";
import { Project } from "../../type";
import ProjectsEmptyState from "./ProjectsEmptyState.vue";

@Component({
    components: { ProjectLink, ProjectsEmptyState },
})
export default class ListOfProjects extends Vue {
    @State
    private readonly is_trove_cat_enabled!: boolean;

    @State
    private readonly projects!: Project[];

    @Getter
    private readonly filtered_projects!: Project[];

    get trove_cat_label(): string {
        return this.$gettext("Browse all…");
    }

    get has_projects(): boolean {
        return this.projects.length > 0;
    }

    get has_filtered_projects(): boolean {
        return this.filtered_projects.length > 0;
    }
}
</script>

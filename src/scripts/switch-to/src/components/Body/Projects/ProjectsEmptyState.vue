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
    <div class="switch-to-projects-empty">
        <projects-empty-state-svg />

        <span class="switch-to-projects-empty-text" v-translate>
            You don't belong to any projects
        </span>

        <trove-cat-link class="tlp-button-secondary switch-to-projects-softwaremap-empty-state">
            {{ trove_cat_label }}
        </trove-cat-link>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import ProjectsEmptyStateSvg from "./ProjectsEmptyStateSvg.vue";
import TroveCatLink from "../TroveCatLink.vue";

@Component({
    components: { TroveCatLink, ProjectsEmptyStateSvg },
})
export default class ProjectsEmptyState extends Vue {
    get trove_cat_label(): string {
        return this.$gettext("Browse all projects");
    }
}
</script>

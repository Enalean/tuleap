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
    <a
        v-bind:href="artifact.html_url"
        class="timetracking-widget-artifact-title"
        v-bind:title="artifact_title"
    >
        <span
            class="cross-ref-badge timetracking-badge-direct-link-to-artifact"
            v-bind:class="badge_color"
        >
            {{ artifact.xref }}
        </span>
        {{ artifact_title }}
    </a>
</template>
<script setup lang="ts">
import type { Artifact } from "@tuleap/plugin-timetracking-rest-api-types";
import { computed } from "vue";

const props = defineProps<{
    artifact: Artifact;
}>();

const badge_color = computed((): string => {
    return "tlp-swatch-" + props.artifact.badge_color;
});

const artifact_title = props.artifact.title !== null ? props.artifact.title : "";
</script>

<style scoped lang="scss">
.timetracking-widget-artifact-title {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.timetracking-badge-direct-link-to-artifact {
    margin: 0 5px 0 0;
}
</style>

<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <h1 class="tlp-pane-title">
        {{ $gettext("Table of contents") }}
    </h1>
    <ol>
        <li v-for="section in sections" v-bind:key="section.artifact.id">
            <a v-bind:href="`#${section.artifact.id}`" class="section-title">
                <slot v-bind:title="section.title"></slot>
            </a>
        </li>
    </ol>
</template>

<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
defineProps<{ sections: readonly ArtidocSection[] }>();
</script>

<style scoped lang="scss">
h1 {
    margin: 0 0 var(--tlp-medium-spacing);
}

ol {
    height: var(--available-height-for-sidebar);
    overflow: hidden scroll;
}

li {
    margin: 0 0 var(--tlp-small-spacing);
}

.section-title {
    color: var(--tlp-dark-color);
    font-size: 0.9rem;
    font-weight: 600;
}
</style>

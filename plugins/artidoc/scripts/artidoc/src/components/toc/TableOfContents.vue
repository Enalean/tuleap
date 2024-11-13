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
        <li v-for="section in sections" v-bind:key="section.id">
            <span v-if="is_sections_loading" class="tlp-skeleton-text"></span>
            <a
                v-else-if="isArtifactSection(section)"
                v-bind:href="`#section-${section.id}`"
                class="table-of-content-section-title"
            >
                {{ section.display_title }}
            </a>
            <template v-else>
                {{ section.display_title }}
            </template>
        </li>
    </ol>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { isArtifactSection } from "@/helpers/artidoc-section.type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";

const { $gettext } = useGettext();

const { sections, is_sections_loading } = strictInject(SECTIONS_STORE);
</script>

<style scoped lang="scss">
h1 {
    display: flex;
    align-items: center;
    height: var(--tlp-form-element-small-height);
    margin: var(--tlp-small-spacing) var(--tlp-medium-spacing);
}

ol {
    height: var(--available-height-for-sidebar);
    padding: 0;
    overflow: hidden scroll;
    list-style-position: inside;
    color: var(--tlp-dimmed-color);
}

li {
    padding: calc(var(--tlp-small-spacing) / 2) var(--tlp-medium-spacing);

    &:first-child {
        padding-top: 0;
    }
}

.section-title,
.table-of-content-section-title {
    color: var(--tlp-dimmed-color);
    font-size: 0.875rem;
    font-weight: 600;
}

@media (max-width: 1024px) {
    .table-of-contents-container {
        padding-top: 0;
    }

    ol {
        height: fit-content;
    }
}
</style>

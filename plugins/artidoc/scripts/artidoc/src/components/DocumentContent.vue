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
    <ol>
        <li
            v-for="section in sections"
            v-bind:key="section.artifact.id"
            v-bind:id="`${section.artifact.id}`"
        >
            <section-content v-bind:section="section" />
        </li>
    </ol>
</template>
<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import SectionContent from "@/components/SectionContent.vue";
defineProps<{ sections: readonly ArtidocSection[] | undefined }>();
</script>
<style lang="scss" scoped>
ol {
    counter-reset: item-without-dot;
}

li {
    padding: 0 0 0 var(--tlp-medium-spacing);
    counter-increment: item-without-dot;
}

li::marker {
    content: counter(item-without-dot);
    color: var(--tlp-dimmed-color-lighter-50);
    font-style: italic;
    font-weight: 600;
}
</style>

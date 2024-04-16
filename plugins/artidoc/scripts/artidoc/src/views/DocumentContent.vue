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
            <article class="document-section">
                <section-title-with-artifact-id
                    v-bind:title="section.title"
                    v-bind:artifact_id="section.artifact.id"
                    class="section-title"
                />
                <div v-dompurify-html="section.description.post_processed_value"></div>
            </article>
        </li>
    </ol>
</template>
<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import SectionTitleWithArtifactId from "@/components/SectionTitleWithArtifactId.vue";

defineProps<{ sections: readonly ArtidocSection[] }>();
</script>
<style lang="scss" scoped>
.document-section {
    display: flex;
    flex-direction: column;
    margin-bottom: 2rem;
}

.section-title {
    margin-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
}

li::marker {
    color: var(--tlp-dimmed-color-lighter-50);
    font-style: italic;
    font-weight: 600;
}
</style>

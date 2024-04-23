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
    <document-layout v-if="sections && sections.length > 0">
        <template #document-content>
            <document-content v-bind:sections="sections">
                <template #section-header="headerProps">
                    <section-title-with-artifact-id
                        v-bind:title="headerProps.title"
                        v-bind:artifact_id="headerProps.artifact_id"
                    />
                </template>
                <template #section-content="contentProps">
                    <section-description
                        v-bind:description_value="contentProps.description_value"
                    />
                </template>
            </document-content>
        </template>
        <template #table-of-contents>
            <table-of-contents v-bind:sections="sections" v-slot="slotProps">
                {{ slotProps.title }}
            </table-of-contents>
        </template>
    </document-layout>
    <div v-else-if="!sections" class="tlp-framed">
        <no-access-state />
    </div>
    <div v-else class="tlp-framed">
        <empty-state />
    </div>
</template>

<script setup lang="ts">
import EmptyState from "@/views/EmptyState.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import DocumentContent from "@/components/DocumentContent.vue";
import TableOfContents from "@/components/TableOfContents.vue";
import NoAccessState from "@/views/NoAccessState.vue";
import SectionTitleWithArtifactId from "@/components/SectionTitleWithArtifactId.vue";
import DocumentLayout from "@/components/DocumentLayout.vue";
import SectionDescription from "@/components/SectionDescription.vue";
import { onMounted } from "vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";

defineProps<{ sections: readonly ArtidocSection[] | undefined }>();

const { scrollToAnchor } = useScrollToAnchor();

onMounted(() => {
    const hash = window.location.hash.slice(1);

    if (hash) {
        scrollToAnchor(hash);
    }
});
</script>

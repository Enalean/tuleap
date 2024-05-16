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
    <article class="document-section">
        <section-title-with-artifact-id
            class="section-header"
            v-if="!is_sections_loading"
            v-bind:title="section.title"
            v-bind:artifact_id="section.artifact.id"
        >
            <template #header-cta>
                <section-editor-cta
                    v-bind:editor_actions="editor_actions"
                    v-bind:is_edit_mode="is_edit_mode"
                />
            </template>
        </section-title-with-artifact-id>
        <section-title-with-artifact-id-skeleton v-else class="section-header" />
        <section-description
            v-bind:artifact_id="section.artifact.id"
            v-bind:editable_description="editable_description"
            v-bind:readonly_description="readonly_description"
            v-bind:input_current_description="inputCurrentDescription"
            v-bind:is_edit_mode="is_edit_mode"
        />
    </article>
</template>

<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import SectionTitleWithArtifactId from "@/components/SectionTitleWithArtifactId.vue";
import SectionDescription from "@/components/SectionDescription.vue";
import useSectionEditor from "@/composables/useSectionEditor";
import SectionEditorCta from "@/components/SectionEditorCta.vue";
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
import SectionTitleWithArtifactIdSkeleton from "@/components/SectionTitleWithArtifactIdSkeleton.vue";

const props = defineProps<{ section: ArtidocSection }>();

const {
    getIsEditMode,
    getEditableDescription,
    getReadonlyDescription,
    editor_actions,
    inputCurrentDescription,
} = useSectionEditor(props.section.description, props.section.artifact.id);

const { is_sections_loading } = useInjectSectionsStore();
const is_edit_mode = getIsEditMode();
const editable_description = getEditableDescription();
const readonly_description = getReadonlyDescription();
</script>

<style lang="scss" scoped>
.document-section {
    display: flex;
    flex-direction: column;
    margin-bottom: 2rem;
}

.section-header {
    margin-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
}
</style>

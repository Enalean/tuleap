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
    <section>
        <div class="artidoc-dropdown-container">
            <section-dropdown
                v-bind:editor="editor"
                v-bind:section="section"
                v-if="!is_sections_loading"
            />
        </div>

        <article
            class="document-section"
            v-bind:class="{
                'document-section-is-being-saved': is_being_saved,
                'document-section-is-just-saved': is_just_saved,
                'document-section-is-just-refreshed': is_just_refreshed,
                'document-section-is-in-error': is_in_error,
                'document-section-is-outdated': is_outdated,
            }"
        >
            <section-header
                class="section-header"
                v-if="!is_sections_loading"
                v-bind:title="title"
                v-bind:input_current_title="editor.inputCurrentTitle"
                v-bind:is_edit_mode="is_edit_mode"
            />
            <section-header-skeleton v-else class="section-header" />
            <section-description
                v-bind:section="section"
                v-bind:editable_description="editable_description"
                v-bind:readonly_description="readonly_description"
                v-bind:input_current_description="editor.inputCurrentDescription"
                v-bind:is_edit_mode="is_edit_mode"
            />
            <section-footer v-bind:editor="editor" v-bind:section="section" />
        </article>
    </section>
</template>

<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import SectionHeader from "@/components/SectionHeader.vue";
import SectionDescription from "@/components/SectionDescription.vue";
import { useSectionEditor } from "@/composables/useSectionEditor";
import SectionDropdown from "@/components/SectionDropdown.vue";
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
import SectionHeaderSkeleton from "@/components/SectionHeaderSkeleton.vue";
import SectionFooter from "@/components/SectionFooter.vue";

const props = defineProps<{ section: ArtidocSection }>();

const {
    is_sections_loading,
    updateSection,
    removeSection,
    getSectionPositionForSave,
    replacePendingByArtifactSection,
} = useInjectSectionsStore();

const editor = useSectionEditor(
    props.section,
    updateSection,
    removeSection,
    getSectionPositionForSave,
    replacePendingByArtifactSection,
);

const is_edit_mode = editor.isSectionInEditMode();
const is_being_saved = editor.isBeeingSaved();
const is_just_saved = editor.isJustSaved();
const is_just_refreshed = editor.isJustRefreshed();
const is_in_error = editor.isInError();
const is_outdated = editor.isOutdated();
const title = editor.getEditableTitle();
const editable_description = editor.getEditableDescription();
const readonly_description = editor.getReadonlyDescription();
</script>

<style lang="scss" scoped>
@use "@tuleap/burningparrot-theme/css/includes/global-variables";
@use "@/themes/includes/zindex";
@use "@/themes/includes/whitespace";

section {
    display: grid;
    grid-template-columns: auto whitespace.$section-right-padding;
}

.artidoc-dropdown-container {
    display: flex;
    z-index: zindex.$dropdown;
    justify-content: center;
    order: 1;
}

.document-section {
    display: flex;
    flex-direction: column;
}

.section-header {
    position: sticky;
    z-index: zindex.$header;
    top: global-variables.$navbar-height;
    margin-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
    background: var(--tuleap-artidoc-section-background);
}
</style>

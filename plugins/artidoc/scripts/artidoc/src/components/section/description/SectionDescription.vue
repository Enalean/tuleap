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
    <section-description-skeleton v-if="is_loading_sections" />
    <template v-if="!is_loading_sections && can_section_be_edited">
        <component
            v-bind:is="async_editor"
            v-bind:section="section"
            v-bind:section_state="section_state"
            v-bind:manage_section_editor_state="manage_section_editor_state"
            v-bind:manage_section_attachment_files="manage_section_attachment_files"
            data-test="editor"
        />
    </template>
    <section-description-read-only
        v-if="!is_loading_sections && !can_section_be_edited"
        v-bind:section="section"
    />
</template>
<script setup lang="ts">
import { defineAsyncComponent, onMounted, computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { loadTooltips } from "@tuleap/tooltip";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { ManageSectionEditorState } from "@/sections/editors/SectionEditorStateManager";
import type { ManageSectionAttachmentFiles } from "@/sections/attachments/SectionAttachmentFilesManager";
import SectionDescriptionSkeleton from "./SectionDescriptionSkeleton.vue";
import SectionDescriptionReadOnly from "./SectionDescriptionReadOnly.vue";

defineProps<{
    section: ReactiveStoredArtidocSection;
    section_state: SectionState;
    manage_section_editor_state: ManageSectionEditorState;
    manage_section_attachment_files: ManageSectionAttachmentFiles;
}>();

const is_loading_sections = strictInject(IS_LOADING_SECTIONS);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

const can_section_be_edited = computed(() => can_user_edit_document);

const async_editor = defineAsyncComponent({
    loader: () => import("./SectionDescriptionEditorProseMirror.vue"),
    loadingComponent: SectionDescriptionReadOnly,
    delay: 0,
});

onMounted(() => {
    loadTooltips();
});
</script>

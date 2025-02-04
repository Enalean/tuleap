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
            v-bind:post_information="post_information"
            v-bind:editable_description="editable_description"
            v-bind:is_edit_mode="is_edit_mode"
            v-bind:upload_file="upload_file"
            v-bind:project_id="project_id"
            v-bind:title="title"
            v-bind:input_section_content="input_section_content"
            v-bind:is_there_any_change="is_there_any_change"
            data-test="editor"
        />
    </template>
    <section-description-read-only
        v-if="!is_loading_sections && !can_section_be_edited"
        v-bind:readonly_value="readonly_description"
    />
</template>
<script setup lang="ts">
import { defineAsyncComponent, onMounted, computed } from "vue";
import { loadTooltips } from "@tuleap/tooltip";
import SectionDescriptionSkeleton from "./SectionDescriptionSkeleton.vue";
import SectionDescriptionReadOnly from "./SectionDescriptionReadOnly.vue";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { UseUploadFileType } from "@/composables/useUploadFile";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import type { FileUploadOptions } from "@tuleap/file-upload";

defineProps<{
    title: string;
    post_information: FileUploadOptions["post_information"];
    editable_description: string;
    readonly_description: string;
    is_edit_mode: boolean;
    upload_file: UseUploadFileType;
    project_id: number;
    input_section_content: EditorSectionContent["inputSectionContent"];
    is_there_any_change: boolean;
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

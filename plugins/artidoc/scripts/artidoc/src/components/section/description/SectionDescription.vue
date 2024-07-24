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
    <section-description-skeleton v-if="is_sections_loading" />
    <template v-else-if="(is_edit_mode || is_prose_mirror) && !is_print_mode">
        <component
            v-bind:is="async_editor"
            v-bind:upload_url="upload_url"
            v-bind:add_attachment_to_waiting_list="add_attachment_to_waiting_list"
            v-bind:editable_description="editable_description"
            v-bind:toggle_has_been_canceled="toggle_has_been_canceled"
            v-bind:input_current_description="input_current_description"
            v-bind:readonly_value="readonly_description"
            v-bind:is_image_upload_allowed="is_image_upload_allowed"
            data-test="editor"
        />
    </template>
    <section-description-read-only v-else v-bind:readonly_value="readonly_description" />
</template>
<script setup lang="ts">
import { defineAsyncComponent, onMounted } from "vue";
import { loadTooltips } from "@tuleap/tooltip";
import SectionDescriptionSkeleton from "./SectionDescriptionSkeleton.vue";
import SectionDescriptionReadOnly from "./SectionDescriptionReadOnly.vue";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { EDITOR_CHOICE } from "@/helpers/editor-choice";

withDefaults(
    defineProps<{
        add_attachment_to_waiting_list: AttachmentFile["addAttachmentToWaitingList"];
        upload_url: string;
        editable_description: string;
        readonly_description: string;
        is_edit_mode: boolean;
        toggle_has_been_canceled: boolean;
        is_image_upload_allowed: boolean;
        input_current_description: EditorSectionContent["inputCurrentDescription"];
        is_print_mode?: boolean;
    }>(),
    {
        is_print_mode: false,
    },
);

const { is_sections_loading } = strictInject(SECTIONS_STORE);
const { is_prose_mirror } = strictInject(EDITOR_CHOICE);

const async_editor = is_prose_mirror.value
    ? defineAsyncComponent({
          loader: () => import("./SectionDescriptionEditorProseMirror.vue"),
          loadingComponent: SectionDescriptionReadOnly,
          delay: 0,
      })
    : defineAsyncComponent({
          loader: () => import("./SectionDescriptionEditor.vue"),
          loadingComponent: SectionDescriptionReadOnly,
          delay: 0,
      });

onMounted(() => {
    loadTooltips();
});
</script>

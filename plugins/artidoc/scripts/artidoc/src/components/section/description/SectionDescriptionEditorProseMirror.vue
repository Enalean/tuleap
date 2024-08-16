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
    <div>
        <div
            ref="content_editor"
            style="display: none"
            v-dompurify-html="editable_description"
        ></div>
        <div class="editor" ref="area_editor">
            <notification-bar
                v-bind:upload_progress="progress"
                v-bind:reset_progress="resetProgressCallback"
                v-bind:message="error_message"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import type { EditorView, UseEditorType } from "@tuleap/prose-mirror-editor";
import { initPluginDropFile, useEditor } from "@tuleap/prose-mirror-editor";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { useUploadFile } from "@/composables/useUploadFile";
import NotificationBar from "@/components/section/description/NotificationBar.vue";
import type { GetText } from "@tuleap/gettext";
import type { PluginDropFile } from "@tuleap/prose-mirror-editor/dist";

const props = defineProps<{
    upload_url: string;
    is_image_upload_allowed: boolean;
    add_attachment_to_waiting_list: AttachmentFile["addAttachmentToWaitingList"];
    is_edit_mode: boolean;
    editable_description: string;
    input_current_description: EditorSectionContent["inputCurrentDescription"];
}>();

let useEditorInstance: UseEditorType | undefined;

const area_editor = ref<HTMLTextAreaElement | null>(null);
const content_editor = ref<HTMLTextAreaElement | null>(null);
const editorView = ref<EditorView | null>(null);
const onChange = (new_text_content: string): void => {
    props.input_current_description(new_text_content);
};

const { file_upload_options, error_message, progress, resetProgressCallback } = useUploadFile(
    props.upload_url,
    props.add_attachment_to_waiting_list,
);

function setupUploadPlugin(gettext_provider: GetText): PluginDropFile {
    return initPluginDropFile(file_upload_options, gettext_provider);
}

function convertDescriptionToHTML(description: string): HTMLElement {
    const parser = new DOMParser();
    return parser.parseFromString(description, "text/html").body;
}

// each time cancel button is clicked, this props is updated to trigger resetContent
watch(
    () => props.is_edit_mode,
    () => {
        if (!props.is_edit_mode) {
            resetProgressCallback();
            if (editorView.value && useEditorInstance) {
                useEditorInstance.resetContent(
                    convertDescriptionToHTML(props.editable_description),
                );
            }
        }
    },
);

onMounted(async () => {
    if (area_editor.value && content_editor.value) {
        useEditorInstance = await useEditor(
            area_editor.value,
            setupUploadPlugin,
            onChange,
            content_editor.value,
        );
        editorView.value = useEditorInstance.editor;
    }
});
</script>

<style lang="scss">
@use "@tuleap/burningparrot-theme/css/includes/global-variables";

/* stylelint-disable selector-class-pattern */
.ProseMirror-menubar {
    position: sticky;

    // Do not display the toolbar under the images in the content
    z-index: 3;
    top: global-variables.$navbar-height;
}
/* stylelint-enable selector-class-pattern */
</style>

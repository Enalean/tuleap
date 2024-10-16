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
        <div class="editor" ref="area_editor"></div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import type { EditorView, UseEditorType, PluginDropFile } from "@tuleap/prose-mirror-editor";
import { initPluginDropFile, useEditor } from "@tuleap/prose-mirror-editor";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import type { GetText } from "@tuleap/gettext";
import type { UseUploadFileType } from "@/composables/useUploadFile";
import type { CrossReference } from "@/stores/useSectionsStore";
import { strictInject } from "@tuleap/vue-strict-inject";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";

const props = defineProps<{
    is_edit_mode: boolean;
    editable_description: string;
    input_current_description: EditorSectionContent["inputCurrentDescription"];
    upload_file: UseUploadFileType;
    project_id: number;
    references: Array<CrossReference>;
}>();

let useEditorInstance: UseEditorType | undefined;

const area_editor = ref<HTMLTextAreaElement | null>(null);
const content_editor = ref<HTMLTextAreaElement | null>(null);
const editorView = ref<EditorView | null>(null);
const onChange = (new_text_content: string): void => {
    props.input_current_description(new_text_content);
};

const { file_upload_options, resetProgressCallback } = props.upload_file;
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
            props.project_id,
            props.references,
            strictInject(TOOLBAR_BUS),
        );
        editorView.value = useEditorInstance.editor;
    }
});
</script>

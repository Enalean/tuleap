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
        <div ref="area_editor"></div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import type { EditorView, UseEditorType } from "@tuleap/prose-mirror-editor";
import { initPluginInput, useEditor } from "@tuleap/prose-mirror-editor";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";

const props = defineProps<{
    toggle_has_been_canceled: boolean;
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

const plugins = ref([initPluginInput(onChange)]);

function convertDescriptionToHTML(description: string): HTMLElement {
    const parser = new DOMParser();
    return parser.parseFromString(description, "text/html").body;
}

// each time cancel button is clicked, this props is updated to trigger resetContent
watch(
    () => props.toggle_has_been_canceled,
    () => {
        if (editorView.value && useEditorInstance) {
            useEditorInstance.resetContent(convertDescriptionToHTML(props.editable_description));
        }
    },
);

onMounted(() => {
    if (area_editor.value && content_editor.value) {
        useEditorInstance = useEditor(area_editor.value, plugins.value, content_editor.value);
        editorView.value = useEditorInstance.editor;
    }
});
</script>

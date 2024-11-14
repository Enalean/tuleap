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
    <div class="editor" ref="area_editor"></div>
</template>
<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import type {
    EditorView,
    UseEditorType,
    PluginDropFile,
    PluginInput,
} from "@tuleap/prose-mirror-editor";
import { initPluginDropFile, initPluginInput, useEditor } from "@tuleap/prose-mirror-editor";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";
import type { GetText } from "@tuleap/gettext";
import type { UseUploadFileType } from "@/composables/useUploadFile";
import type { CrossReference } from "@/stores/useSectionsStore";
import { strictInject } from "@tuleap/vue-strict-inject";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { artidoc_editor_schema } from "../mono-editor/artidoc-editor-schema";
import { renderArtidocSectionNode } from "@/components/section/description/render-artidoc-section-node";
import { setupMonoEditorPlugins } from "../mono-editor/jump-to-section-node";

const toolbar_bus = strictInject(TOOLBAR_BUS);

const props = defineProps<{
    title: string;
    is_edit_mode: boolean;
    editable_description: string;
    upload_url: string;
    input_section_content: EditorSectionContent["inputSectionContent"];
    upload_file: UseUploadFileType;
    project_id: number;
    references: Array<CrossReference>;
}>();

let useEditorInstance: UseEditorType | undefined;

const area_editor = ref<HTMLElement | null>(null);
const editorView = ref<EditorView | null>(null);

const { file_upload_options, resetProgressCallback } = props.upload_file;

function setupUploadPlugin(gettext_provider: GetText): PluginDropFile {
    return initPluginDropFile(file_upload_options, gettext_provider);
}

const setupInputPlugin = (): PluginInput =>
    initPluginInput((content: HTMLElement) => {
        props.input_section_content(
            String(content.querySelector("artidoc-section-title")?.textContent),
            String(content.querySelector("artidoc-section-description")?.innerHTML),
        );
    });

// each time cancel button is clicked, this props is updated to trigger resetContent
watch(
    () => props.is_edit_mode,
    () => {
        if (!props.is_edit_mode) {
            resetProgressCallback();
            if (editorView.value && useEditorInstance) {
                const artidoc_section = renderArtidocSectionNode(
                    props.title,
                    props.editable_description,
                );
                useEditorInstance.resetContent(artidoc_section);
            }
        }
    },
);

onMounted(async () => {
    if (area_editor.value) {
        const is_upload_allowed = props.upload_url !== "";

        useEditorInstance = await useEditor(
            area_editor.value,
            setupUploadPlugin,
            setupInputPlugin,
            () => setupMonoEditorPlugins(toolbar_bus),
            is_upload_allowed,
            renderArtidocSectionNode(props.title, props.editable_description),
            props.project_id,
            props.references,
            toolbar_bus,
            artidoc_editor_schema,
        );
        editorView.value = useEditorInstance.editor;
    }
});
</script>
<style lang="scss">
artidoc-section {
    display: block;
}

artidoc-section-title {
    display: block;
    margin: 0 0 var(--tlp-large-spacing);
    padding: 0 0 var(--tlp-small-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
    color: var(--tlp-dark-color);
    font-size: 36px;
    font-weight: 600;
    line-height: 40px;
}

artidoc-section-description {
    display: block;
}
</style>

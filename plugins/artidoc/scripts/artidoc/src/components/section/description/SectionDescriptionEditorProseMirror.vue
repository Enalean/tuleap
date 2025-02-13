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
    SerializeDOM,
} from "@tuleap/prose-mirror-editor";
import { initPluginDropFile, initPluginInput, useEditor } from "@tuleap/prose-mirror-editor";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { GetText } from "@tuleap/gettext";
import { getSectionFileUploader } from "@/sections/attachments/SectionFileUploader";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { ManageSectionEditorState } from "@/sections/editors/SectionEditorStateManager";
import type { ManageSectionAttachmentFiles } from "@/sections/attachments/SectionAttachmentFilesManager";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";
import { PROJECT_ID } from "@/project-id-injection-key";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import { NOTIFICATION_COLLECTION } from "@/sections/notifications/notification-collection-injection-key";
import { artidoc_editor_schema } from "../mono-editor/artidoc-editor-schema";
import { renderArtidocSectionNode } from "@/components/section/description/render-artidoc-section-node";
import { setupMonoEditorPlugins } from "../mono-editor/setupMonoEditorPlugins";
import { getProjectIdFromSection } from "@/helpers/get-project-id-from-section";

const toolbar_bus = strictInject(TOOLBAR_BUS);

const props = defineProps<{
    section: ReactiveStoredArtidocSection;
    section_state: SectionState;
    manage_section_editor_state: ManageSectionEditorState;
    manage_section_attachment_files: ManageSectionAttachmentFiles;
}>();

let useEditorInstance: UseEditorType | undefined;

const area_editor = ref<HTMLElement | null>(null);
const editorView = ref<EditorView | null>(null);

const file_uploads_collection = strictInject(FILE_UPLOADS_COLLECTION);
const current_project_id = strictInject(PROJECT_ID);

const { file_upload_options, resetProgressCallback } = getSectionFileUploader(
    props.section.value.id,
    props.manage_section_attachment_files,
    file_uploads_collection,
    strictInject(NOTIFICATION_COLLECTION),
    strictInject(UPLOAD_MAX_SIZE),
);

function setupUploadPlugin(gettext_provider: GetText): PluginDropFile {
    return initPluginDropFile(file_upload_options, gettext_provider);
}

const setupInputPlugin = (serializer: SerializeDOM): PluginInput =>
    initPluginInput(serializer, (content: HTMLElement) => {
        props.manage_section_editor_state.setEditedContent(
            String(content.querySelector("artidoc-section-title")?.textContent),
            String(content.querySelector("artidoc-section-description")?.innerHTML),
        );
    });

// each time cancel button is clicked, this props is updated to trigger resetContent
watch(
    () => props.section_state.is_section_in_edit_mode.value,
    (is_section_in_edit_mode) => {
        if (is_section_in_edit_mode) {
            return;
        }

        resetProgressCallback();
        if (
            editorView.value &&
            useEditorInstance &&
            props.section_state.is_editor_reset_needed.value
        ) {
            const artidoc_section = renderArtidocSectionNode(props.section);
            useEditorInstance.resetContent(artidoc_section);
            props.manage_section_editor_state.markEditorAsReset();
        }
    },
);

onMounted(async () => {
    if (!area_editor.value) {
        return;
    }

    const is_upload_allowed =
        props.manage_section_attachment_files.getPostInformation().upload_url !== "";

    useEditorInstance = await useEditor(
        area_editor.value,
        setupUploadPlugin,
        setupInputPlugin,
        () => setupMonoEditorPlugins(toolbar_bus),
        is_upload_allowed,
        renderArtidocSectionNode(props.section),
        getProjectIdFromSection(props.section.value) ?? current_project_id,
        toolbar_bus,
        artidoc_editor_schema,
    );
    editorView.value = useEditorInstance.editor;
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

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
        <section-header
            class="section-header"
            v-bind:title="editable_title"
            v-bind:input_current_title="inputCurrentTitle"
            v-bind:is_edit_mode="false"
        />
        <section-description
            v-bind:editable_description="editable_description"
            v-bind:readonly_description="getReadonlyDescription()"
            v-bind:input_current_description="inputCurrentDescription"
            v-bind:is_edit_mode="false"
            v-bind:add_attachment_to_waiting_list="addAttachmentToWaitingList"
            v-bind:upload_url="upload_url"
            v-bind:is_image_upload_allowed="is_image_upload_allowed"
        />
        <section-footer v-bind:editor="editor" v-bind:section="section" />
    </article>
</template>

<script setup lang="ts">
import type { ArtifactSection } from "@/helpers/artidoc-section.type";
import SectionFooter from "@/components/section/footer/SectionFooter.vue";
import SectionHeader from "@/components/section/header/SectionHeader.vue";
import SectionDescription from "@/components/section/description/SectionDescription.vue";
import { useAttachmentFile } from "@/composables/useAttachmentFile";
import { ref } from "vue";
import { useSectionEditor } from "@/composables/useSectionEditor";

const props = defineProps<{ section: ArtifactSection }>();

const {
    upload_url,
    addAttachmentToWaitingList,
    mergeArtifactAttachments,
    setWaitingListAttachments,
} = useAttachmentFile(ref(props.section.attachments ? props.section.attachments.field_id : 0));

const editor = useSectionEditor(props.section, mergeArtifactAttachments, setWaitingListAttachments);

const { is_image_upload_allowed } = editor.editor_state;

const {
    inputCurrentDescription,
    inputCurrentTitle,
    editable_title,
    editable_description,
    getReadonlyDescription,
} = editor.editor_section_content;
</script>

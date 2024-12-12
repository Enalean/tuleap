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
    <article>
        <section-header
            class="section-header"
            v-bind:title="editable_title"
            v-bind:is_print_mode="true"
        />
        <section-description
            v-bind:title="editable_title"
            v-bind:editable_description="editable_description"
            v-bind:readonly_description="readonly_description"
            v-bind:input_section_content="noop"
            v-bind:is_edit_mode="false"
            v-bind:add_attachment_to_waiting_list="noop"
            v-bind:upload_url="upload_url"
            v-bind:is_image_upload_allowed="is_image_upload_allowed"
            v-bind:is_print_mode="true"
            v-bind:upload_file="UploadFileStub.uploadNotInProgress()"
            v-bind:is_there_any_change="false"
            v-bind:project_id="section.artifact.tracker.project.id"
        />
    </article>
</template>

<script setup lang="ts">
import type { ArtifactSection } from "@/helpers/artidoc-section.type";
import SectionHeader from "@/components/section/header/SectionHeader.vue";
import SectionDescription from "@/components/section/description/SectionDescription.vue";
import { computed, ref } from "vue";
import { useEditorSectionContent } from "@/composables/useEditorSectionContent";
import { UploadFileStub } from "@/helpers/stubs/UploadFileStub";

const props = defineProps<{ section: ArtifactSection }>();

const content = computed(() =>
    useEditorSectionContent(ref(props.section), {
        showActionsButtons: noop,
        hideActionsButtons: noop,
    }),
);

const editable_title = computed(() => content.value.editable_title.value);
const readonly_description = computed(() => content.value.getReadonlyDescription());

function noop(): void {}
const upload_url = "";
const is_image_upload_allowed = false;
const editable_description = "";
</script>

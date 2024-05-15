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
        <ckeditor
            v-bind:editor="editor"
            v-bind:model-value="toValue(editable_description)"
            v-bind:config="editorConfig"
            v-on:input="input_current_description"
        />
    </div>
</template>
<script setup lang="ts">
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import type { use_section_editor_type } from "@/composables/useSectionEditor";
import { toValue } from "vue";
defineProps<{
    artifact_id: number;
    editable_description: string;
    input_current_description: use_section_editor_type["inputCurrentDescription"];
}>();
const editorConfig = {};
const editor = ClassicEditor;
</script>

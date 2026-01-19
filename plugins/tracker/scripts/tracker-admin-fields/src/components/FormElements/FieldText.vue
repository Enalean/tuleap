<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
  -->

<template>
    <div class="tlp-form-element">
        <label-for-field v-bind:field="field" />
        <textarea
            class="tlp-textarea"
            v-bind:id="`textarea_${field.field_id}`"
            v-bind:rows="field.specific_properties.rows"
            v-bind:value="field.specific_properties.default_value"
            v-bind:data-project-id="project_id"
            ref="textarea"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import type { TextFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";
import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";
import { getLocaleWithDefault } from "@tuleap/locale";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_ID } from "../../type";

defineProps<{
    field: TextFieldStructure;
}>();

const project_id = strictInject(PROJECT_ID);

const textarea = ref<InstanceType<typeof HTMLTextAreaElement>>();

onMounted(() => {
    if (textarea.value === undefined) {
        return;
    }

    const user_locale = getLocaleWithDefault(document);
    const creator = RichTextEditorsCreator(
        document,
        UploadImageFormFactory(document, user_locale),
        RichTextEditorFactory.forBurningParrotWithFormatSelector(document, user_locale),
    );
    creator.createTextFieldEditor(textarea.value);
});
</script>
<style lang="scss">
@use "pkg:@tuleap/plugin-tracker-rich-text-editor";
</style>

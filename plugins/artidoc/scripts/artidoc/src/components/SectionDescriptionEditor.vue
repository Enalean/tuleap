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
        <textarea ref="area_editor" v-bind:value="toValue(editable_description)"></textarea>
    </div>
</template>
<script setup lang="ts">
import type { SectionEditor } from "@/composables/useSectionEditor";
import type * as ckeditor from "ckeditor4";
import { toValue, ref, onMounted, onBeforeUnmount } from "vue";
import { config } from "@tuleap/ckeditor-config";
import { CURRENT_LOCALE } from "@/locale-injection-key";
import { strictInject } from "@tuleap/vue-strict-inject";

// CKEDITOR is injected by the backend
// eslint-disable-next-line
import eventInfo = CKEDITOR.eventInfo;
const { language } = strictInject(CURRENT_LOCALE);

const props = defineProps<{
    editable_description: string;
    input_current_description: SectionEditor["inputCurrentDescription"];
}>();

const area_editor = ref<HTMLTextAreaElement | null>(null);
const editor = ref<ckeditor.default.editor | null>(null);

const onChange = (editor_value: string | undefined): void => {
    if (editor_value) {
        props.input_current_description(editor_value);
    }
};

const onInstanceReady = (event: eventInfo<ckeditor.default.eventDataTypes>): void => {
    if (editor.value) {
        editor.value.on("change", () => onChange(editor.value?.getData()));

        editor.value.on("mode", (): void => {
            if (editor.value && editor.value.mode === "source") {
                const editable = editor.value.editable();
                editable.attachListener(editable, "input", () => {
                    onChange(editor.value?.getData());
                });
            }
        });

        // disable drag and drop
        // @ts-expect-error: CKEDITOR is injected by the backend
        // eslint-disable-next-line
        CKEDITOR.plugins.clipboard.preventDefaultDropOnElement(event.editor.document);

        // Ajust the height of the editor to the content. The timeout is required to load autogrow plugin correctly after editor initialization
        setTimeout(() => {
            if (editor.value) {
                editor.value.execCommand("autogrow");
            }
        });
        editor.value.document.getBody().setStyle("font-size", "16px");
    }
};

onMounted(() => {
    if (editor.value !== null) {
        editor.value.destroy();
    }

    if (area_editor.value) {
        // @ts-expect-error: CKEDITOR is injected by the backend
        // eslint-disable-next-line
        editor.value = CKEDITOR.replace(area_editor.value, {
            ...config,
            language,
            extraPlugins: "autogrow",
            autoGrow_minHeight: 200,
            autoGrow_bottomSpace: 50,
            height: "auto",
            resize_enabled: false,
        });

        if (editor.value !== null) {
            editor.value.on("instanceReady", onInstanceReady);
        }
    }
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});
</script>

<style lang="scss" scoped>
@use "@/themes/includes/zindex";

div {
    z-index: zindex.$editor;
}
</style>

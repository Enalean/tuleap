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
        <h1 class="section-title">
            <textarea
                type="text"
                class="tlp-textarea"
                v-if="is_edit_mode"
                v-model="title_to_edit"
                v-on:input="onTitleChange"
                v-bind:placeholder="placeholder"
                ref="textarea"
                data-test="title-input"
                rows="1"
            ></textarea>
            <template v-else>
                {{ title }}
            </template>
        </h1>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";
import { useGettext } from "vue3-gettext";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";

const props = defineProps<{
    title: string;
    is_edit_mode: boolean;
    input_current_title: EditorSectionContent["inputCurrentTitle"];
}>();

const { scrollToAnchor } = useScrollToAnchor();
const { $gettext } = useGettext();

const placeholder = $gettext("Section without title");

const textarea = ref<HTMLTextAreaElement | undefined>(undefined);
const title_to_edit = ref(props.title);

watch(
    () => textarea.value,
    () => {
        if (textarea.value) {
            textarea.value.focus();
            scrollToAnchor(textarea.value.closest("li") || textarea.value);
            adjustHeightOfTextareaToContent(textarea.value);
        }
    },
);

function onTitleChange(event: Event): void {
    if (!(event.target instanceof HTMLTextAreaElement)) {
        return;
    }

    adjustHeightOfTextareaToContent(event.target);

    props.input_current_title(event.target.value);
}

function adjustHeightOfTextareaToContent(textarea: HTMLTextAreaElement): void {
    const random_small_value_to_force_reset_scrollHeight = 5;
    textarea.style.height = random_small_value_to_force_reset_scrollHeight + "px";

    const extra_height_to_avoid_scrollbar = 5;
    textarea.style.height = extra_height_to_avoid_scrollbar + textarea.scrollHeight + "px";
}
</script>

<style lang="scss" scoped>
h1 {
    margin: 0;
    padding-bottom: var(--tlp-small-spacing);
    color: var(--tlp-dark-color);
}

a {
    margin: 0 0 0 var(--tlp-medium-spacing);
    font-size: 1rem;
    font-weight: 400;
}

textarea {
    // inherit styles from the parent h1
    font-size: inherit;
    font-weight: inherit;
    line-height: inherit;
    resize: none;
}
</style>

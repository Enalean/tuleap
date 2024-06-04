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
        <span class="editor-cta"><slot name="header-cta"></slot></span>
        <h1>
            <textarea
                type="text"
                class="tlp-textarea"
                v-if="is_edit_mode"
                v-model="title_to_edit"
                v-on:input="onTitleChange"
                v-bind:placeholder="placeholder"
                ref="textarea"
                rows="1"
            ></textarea>
            <template v-else>
                {{ title }}
            </template>
            <a v-bind:href="artifact_url">#{{ artifact_id }}</a>
        </h1>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";
import type { SectionEditor } from "@/composables/useSectionEditor";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    artifact_id: number;
    title: string;
    is_edit_mode: boolean;
    input_current_title: SectionEditor["inputCurrentTitle"];
}>();
const artifact_url = `/plugins/tracker/?aid=${props.artifact_id}`;

const { scrollToAnchor } = useScrollToAnchor();
const { $gettext } = useGettext();

const placeholder = $gettext("Section without title");

const textarea = ref<HTMLTextAreaElement | undefined>(undefined);
const title_to_edit = ref(props.title);

onMounted(() => {
    const hash = window.location.hash.slice(1);
    if (hash) {
        scrollToAnchor(hash);
    }
});

watch(
    () => textarea.value,
    () => {
        if (textarea.value) {
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
    align-items: center;
    margin: 0;
    padding-bottom: var(--tlp-small-spacing);
    color: var(--tlp-dark-color);
}

.editor-cta {
    margin: var(--tlp-small-spacing) 0 0 var(--tlp-small-spacing);
    float: right;

    &:empty {
        display: none;
    }
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

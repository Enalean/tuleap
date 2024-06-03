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
            <input
                type="text"
                class="tlp-input tlp-input-large"
                v-if="is_edit_mode"
                v-bind:value="title"
                v-on:input="onTitleChange"
            />
            <template v-else>
                {{ title }}
            </template>
            <a v-bind:href="artifact_url">#{{ artifact_id }}</a>
        </h1>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";
import type { use_section_editor_type } from "@/composables/useSectionEditor";

const props = defineProps<{
    artifact_id: number;
    title: string;
    is_edit_mode: boolean;
    input_current_title: use_section_editor_type["inputCurrentTitle"];
}>();
const artifact_url = `/plugins/tracker/?aid=${props.artifact_id}`;

const { scrollToAnchor } = useScrollToAnchor();

onMounted(() => {
    const hash = window.location.hash.slice(1);
    if (hash) {
        scrollToAnchor(hash);
    }
});

function onTitleChange(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }

    props.input_current_title(event.target.value);
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

.tlp-input-large {
    font-size: 36px;
    font-weight: 600;
    line-height: 40px;
}
</style>

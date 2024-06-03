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
    <span class="section-editor-cta" v-if="is_section_editable && !is_edit_mode">
        <button
            v-on:click="enableEditor()"
            type="button"
            class="tlp-button-primary tlp-button-mini"
        >
            <i class="fa-solid fa-pencil tlp-button-icon" aria-hidden="true"></i>
            <span>{{ $gettext("Edit") }}</span>
        </button>
    </span>
</template>

<script setup lang="ts">
import type { SectionEditor } from "@/composables/useSectionEditor";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const props = defineProps<{
    editor: SectionEditor;
}>();
const { enableEditor } = props.editor.editor_actions;
const { is_section_editable } = props.editor;
const is_edit_mode = props.editor.isSectionInEditMode();
</script>

<style lang="scss" scoped>
.cancel-button {
    margin-right: var(--tlp-small-spacing);
}
</style>

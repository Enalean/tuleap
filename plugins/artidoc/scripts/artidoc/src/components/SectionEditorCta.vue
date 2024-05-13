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
    <button
        v-if="!is_edit_mode"
        v-on:click="enableEditor"
        type="button"
        class="tlp-button-primary tlp-button-mini"
    >
        <i class="fa-solid fa-tlp-user-pencil tlp-button-icon" aria-hidden="true"></i>
        <span>{{ $gettext("edit") }}</span>
    </button>
    <button
        v-if="is_edit_mode"
        v-on:click="cancelEditor"
        type="button"
        class="cancel-button tlp-button-primary tlp-button-mini"
    >
        <i class="fa-solid fa-xmark tlp-button-icon" aria-hidden="true"></i>
        <span>{{ $gettext("cancel") }}</span>
    </button>
    <button
        v-if="is_edit_mode"
        v-on:click="saveEditor"
        type="button"
        class="tlp-button-primary tlp-button-mini"
    >
        <i class="fa-solid fa-tlp-add-to-backlog tlp-button-icon" aria-hidden="true"></i>
        <span>{{ $gettext("save") }}</span>
    </button>
</template>
<script setup lang="ts">
import type { use_section_editor_actions_type } from "@/composables/useSectionEditor";
import { useGettext } from "vue3-gettext";
const { $gettext } = useGettext();
const props = defineProps<{
    is_edit_mode: boolean;
    editor_actions: use_section_editor_actions_type;
}>();
const { cancelEditor, enableEditor, saveEditor } = props.editor_actions;
</script>
<style lang="scss" scoped>
.cancel-button {
    margin-right: var(--tlp-small-spacing);
}
</style>

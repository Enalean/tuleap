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
    <div
        v-if="is_section_in_edit_mode || has_title_level_been_changed"
        class="document-section-cancel-save-buttons"
    >
        <button
            v-on:click="close_section_editor.closeAndCancelEditor"
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-button-large"
            data-test="cancel-button"
        >
            <i class="fa-solid fa-xmark tlp-button-icon" aria-hidden="true"></i>
            <span>{{ $gettext("Cancel") }}</span>
        </button>
        <button
            v-on:click="save_section.save"
            v-bind:disabled="!is_save_allowed"
            type="button"
            class="tlp-button-primary tlp-button-large"
            data-test="save-button"
        >
            <i class="fa-solid fa-floppy-disk tlp-button-icon" aria-hidden="true"></i>
            <span>{{ $gettext("Save") }}</span>
        </button>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";
import type { SaveSection } from "@/sections/save/SectionSaver";

const props = defineProps<{
    section_state: SectionState;
    close_section_editor: CloseSectionEditor;
    save_section: SaveSection;
}>();

const { $gettext } = useGettext();
const { is_section_in_edit_mode, has_title_level_been_changed, is_save_allowed } =
    props.section_state;
</script>

<style lang="scss" scoped>
div {
    display: flex;
    gap: var(--tlp-medium-spacing);
    justify-content: center;
}
</style>

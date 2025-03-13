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
        v-if="
            section_state.is_section_in_edit_mode.value ||
            section_state.has_title_level_been_changed.value
        "
        class="section-footer"
    >
        <not-found-error v-if="is_not_found" />
        <generic-error
            v-else-if="is_in_error"
            v-bind:section="section"
            v-bind:error_message="error_message"
        />
        <outdated-section-warning
            v-else-if="is_outdated"
            v-bind:save_section="save_section"
            v-bind:refresh_section="refresh_section"
        />

        <section-editor-save-cancel-buttons
            v-bind:section_state="section_state"
            v-bind:save_section="save_section"
            v-bind:close_section_editor="close_section_editor"
        />
    </div>
</template>

<script setup lang="ts">
import SectionEditorSaveCancelButtons from "./SectionEditorSaveCancelButtons.vue";
import NotFoundError from "./NotFoundError.vue";
import OutdatedSectionWarning from "./OutdatedSectionWarning.vue";
import GenericError from "./GenericError.vue";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";
import type { RefreshSection } from "@/sections/update/SectionRefresher";
import type { SaveSection } from "@/sections/save/SectionSaver";

const props = defineProps<{
    section: ReactiveStoredArtidocSection;
    section_state: SectionState;
    close_section_editor: CloseSectionEditor;
    refresh_section: RefreshSection;
    save_section: SaveSection;
}>();

const { error_message, is_outdated, is_in_error, is_not_found } = props.section_state;
</script>

<style scoped lang="scss">
@use "@/themes/includes/zindex";

.section-footer {
    position: sticky;
    z-index: zindex.$footer;
    bottom: 0;

    /*
    Artificially augment the width of the footer so that it covers the box-shadow of
    the editor when the latter has the focus and goes below the footer.
    */
    width: calc(100% + 2 * var(--tlp-shadow-focus-width));
    margin: 0 0 0 calc(-1 * var(--tlp-shadow-focus-width));
    padding: var(--tlp-medium-spacing) 0 0;
}
</style>

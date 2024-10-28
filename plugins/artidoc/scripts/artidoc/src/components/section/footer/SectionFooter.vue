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
    <div v-if="is_section_editable" class="section-footer section-footer-with-background">
        <not-found-error v-if="is_not_found" />
        <generic-error
            v-else-if="is_in_error"
            v-bind:section="section"
            v-bind:error_message="error_message"
        />
        <outdated-section-warning
            v-else-if="is_outdated"
            v-bind:editor_actions="editor.editor_actions"
        />

        <section-editor-save-cancel-buttons v-bind:editor="editor" />
    </div>
</template>

<script setup lang="ts">
import type { SectionEditor } from "@/composables/useSectionEditor";
import SectionEditorSaveCancelButtons from "./SectionEditorSaveCancelButtons.vue";
import NotFoundError from "./NotFoundError.vue";
import OutdatedSectionWarning from "./OutdatedSectionWarning.vue";
import GenericError from "./GenericError.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

const props = defineProps<{ section: ArtidocSection; editor: SectionEditor }>();

const { error_message, is_outdated, is_in_error, is_not_found } = props.editor.editor_error;
const { is_section_editable } = props.editor.editor_state;
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
    padding: var(--tlp-medium-spacing) 0;

    &-with-background {
        background: var(--tuleap-artidoc-section-background);
    }
}
</style>

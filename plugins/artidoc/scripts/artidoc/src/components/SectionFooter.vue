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
    <div v-if="is_section_editable" class="section-footer">
        <not-found-error v-if="is_not_found_error" />
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
import type { use_section_editor_type } from "@/composables/useSectionEditor";
import SectionEditorSaveCancelButtons from "@/components/SectionEditorSaveCancelButtons.vue";
import NotFoundError from "@/components/NotFoundError.vue";
import OutdatedSectionWarning from "@/components/OutdatedSectionWarning.vue";
import GenericError from "@/components/GenericError.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

const props = defineProps<{ section: ArtidocSection; editor: use_section_editor_type }>();

const error_message = props.editor.getErrorMessage();
const is_outdated = props.editor.isOutdated();
const is_in_error = props.editor.isInError();
const is_not_found_error = props.editor.isNotFoundError();
const { is_section_editable } = props.editor;
</script>

<style scoped lang="scss">
.section-footer {
    position: sticky;
    z-index: 2;
    bottom: 0;
    padding: var(--tlp-medium-spacing) 0;
    background: var(--tuleap-artidoc-section-background);
}
</style>

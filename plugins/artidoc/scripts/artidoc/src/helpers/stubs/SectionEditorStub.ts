/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import type {
    EditorState,
    SectionEditor,
    SectionEditorActions,
} from "@/composables/useSectionEditor";
import { ref, computed } from "vue";
import type { EditorErrors } from "@/composables/useEditorErrors";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";

const noop = (): void => {};

const editor_state_stub: EditorState = {
    is_image_upload_allowed: computed(() => false),
    is_section_editable: computed(() => false),
    is_section_in_edit_mode: ref(false),
    isBeingSaved: () => false,
    isJustSaved: () => false,
    isJustRefreshed: () => false,
};

const editor_error_stub: EditorErrors = {
    handleError: noop,
    is_in_error: ref(false),
    is_outdated: ref(false),
    is_not_found: ref(false),
    resetErrorStates: noop,
    getErrorMessage: () => "",
};

const editor_section_content_stub: EditorSectionContent = {
    inputCurrentTitle: noop,
    inputCurrentDescription: noop,
    editable_title: ref(""),
    editable_description: ref(""),
    getReadonlyDescription: () => "",
    resetContent: noop,
};

const editor_actions_stub: SectionEditorActions = {
    enableEditor: noop,
    saveEditor: noop,
    forceSaveEditor: noop,
    cancelEditor: noop,
    refreshSection: noop,
};

export const SectionEditorStub = {
    withoutEditableSection: (): SectionEditor => ({
        editor_state: editor_state_stub,
        editor_error: editor_error_stub,
        editor_actions: editor_actions_stub,
        editor_section_content: editor_section_content_stub,
        clearGlobalNumberOfOpenEditorForTests: noop,
    }),

    withEditableSection: (): SectionEditor => {
        return {
            ...SectionEditorStub.withoutEditableSection(),
            editor_state: {
                ...editor_state_stub,
                is_section_editable: computed(() => true),
            },
        };
    },

    inEditMode: (): SectionEditor => {
        const section_editable = SectionEditorStub.withEditableSection();
        return {
            ...section_editable,
            editor_state: {
                ...section_editable.editor_state,
                is_section_in_edit_mode: ref(true),
            },
        };
    },
};

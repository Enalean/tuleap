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
import type { SectionEditor } from "@/composables/useSectionEditor";
import { computed, ref } from "vue";
import { noop } from "@/helpers/noop";

export const SectionEditorStub = {
    withoutEditableSection: (): SectionEditor => ({
        editor_state: {
            is_save_allowed: computed(() => false),
            is_image_upload_allowed: computed(() => false),
            is_section_editable: computed(() => false),
            is_section_in_edit_mode: ref(false),
            isBeingSaved: () => false,
            isJustSaved: () => false,
            isJustRefreshed: () => false,
        },
        editor_error: {
            handleError: noop,
            is_in_error: ref(false),
            is_outdated: ref(false),
            is_not_found: ref(false),
            error_message: ref(""),
            resetErrorStates: noop,
        },
        editor_actions: {
            enableEditor: noop,
            saveEditor: noop,
            forceSaveEditor: noop,
            cancelEditor: noop,
            refreshSection: noop,
            deleteSection: noop,
        },
        editor_section_content: {
            inputSectionContent: noop,
            editable_title: ref(""),
            editable_description: ref(""),
            getReadonlyDescription: () => "",
            resetContent: noop,
        },
    }),

    withEditableSection: (): SectionEditor => {
        const editor = SectionEditorStub.withoutEditableSection();

        return {
            ...editor,
            editor_state: {
                ...editor.editor_state,
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

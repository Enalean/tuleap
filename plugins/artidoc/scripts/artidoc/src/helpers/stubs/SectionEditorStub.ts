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
import type { use_section_editor_type } from "@/composables/useSectionEditor";
import { ref, computed } from "vue";

const noop = (): void => {};

export const SectionEditorStub = {
    withoutEditableSection: (): use_section_editor_type => ({
        is_section_editable: computed(() => false),
        isSectionInEditMode: () => ref(false),
        isBeeingSaved: () => ref(false),
        isJustSaved: () => ref(false),
        isJustRefreshed: () => ref(false),
        isInError: () => ref(false),
        isOutdated: () => ref(false),
        isNotFoundError: () => ref(false),
        editor_actions: {
            enableEditor: noop,
            saveEditor: noop,
            forceSaveEditor: noop,
            cancelEditor: noop,
            refreshSection: noop,
        },
        inputCurrentTitle: noop,
        inputCurrentDescription: noop,
        getEditableTitle: () => ref(""),
        getEditableDescription: () => ref(""),
        getReadonlyDescription: () => ref(""),
        getErrorMessage: () => ref(""),
        clearGlobalNumberOfOpenEditorForTests: noop,
    }),

    withEditableSection: (): use_section_editor_type => ({
        ...SectionEditorStub.withoutEditableSection(),
        is_section_editable: computed(() => true),
    }),

    inEditMode: (): use_section_editor_type => ({
        ...SectionEditorStub.withEditableSection(),
        isSectionInEditMode: () => ref(true),
    }),
};

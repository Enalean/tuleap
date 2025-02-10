/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type { SectionState } from "@/sections/SectionStateBuilder";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { getSectionHtmlDescription } from "@/helpers/get-section-html-description";

export type ManageSectionEditorState = {
    setEditedContent(new_title: string, new_description: string): void;
    resetContent(): void;
    markEditorAsReset(): void;
};

export const getSectionEditorStateManager = (
    section: ReactiveStoredArtidocSection,
    section_state: SectionState,
): ManageSectionEditorState => {
    const original_title = section.value.display_title;
    const original_description = getSectionHtmlDescription(section);

    return {
        setEditedContent(new_title, new_description): void {
            const has_content_been_edited =
                new_title !== original_title || new_description !== original_description;

            section_state.is_editor_reset_needed.value = has_content_been_edited;
            section_state.is_section_in_edit_mode.value = has_content_been_edited;

            section_state.edited_title.value = new_title;
            section_state.edited_description.value = new_description;
        },
        resetContent(): void {
            section_state.edited_title.value = section.value.display_title;
            section_state.edited_description.value = getSectionHtmlDescription(section);
            section_state.is_section_in_edit_mode.value = false;
        },
        markEditorAsReset(): void {
            section_state.is_editor_reset_needed.value = false;
        },
    };
};

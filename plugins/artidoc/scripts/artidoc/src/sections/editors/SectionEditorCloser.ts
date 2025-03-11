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

import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { ManageErrorState } from "@/sections/states/SectionErrorManager";
import type { ManageSectionEditorState } from "@/sections/editors/SectionEditorStateManager";
import type { ManageSectionAttachmentFiles } from "@/sections/attachments/SectionAttachmentFilesManager";
import type { FileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";
import type { RemoveSections } from "@/sections/remove/SectionsRemover";
import { isPendingSection } from "@/helpers/artidoc-section.type";

export type CloseSectionEditor = {
    closeEditor(): void;
    closeAndCancelEditor(): void;
};

export const getSectionEditorCloser = (
    section: ReactiveStoredArtidocSection,
    manage_error_state: ManageErrorState,
    manage_section_editor_state: ManageSectionEditorState,
    manage_section_attachments: ManageSectionAttachmentFiles,
    remove_sections: RemoveSections,
    file_uploads_collection: FileUploadsCollection,
): CloseSectionEditor => {
    const closeEditor = (): void => {
        manage_section_editor_state.resetContent();
        manage_error_state.resetErrorStates();
        manage_section_attachments.setWaitingListAttachments([]);
    };

    return {
        closeEditor,
        closeAndCancelEditor(): void {
            closeEditor();
            file_uploads_collection.cancelSectionUploads(section.value.id);

            if (isPendingSection(section.value)) {
                remove_sections.removeSection(section);
            }
        },
    };
};

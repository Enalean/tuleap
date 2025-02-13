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

import { isArtifactSection, isFreetextSection } from "@/helpers/artidoc-section.type";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { getSection } from "@/helpers/rest-querier";
import type { Fault } from "@tuleap/fault";
import type { UpdateSections } from "@/sections/update/SectionsUpdater";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { ManageErrorState } from "@/sections/states/SectionErrorManager";
import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";

export type RefreshSection = {
    refreshSection: () => void;
};

export const getSectionRefresher = (
    section: ReactiveStoredArtidocSection,
    section_state: SectionState,
    manage_error_state: ManageErrorState,
    update_sections: UpdateSections,
    close_section_editor: CloseSectionEditor,
): RefreshSection => ({
    refreshSection(): void {
        if (!isArtifactSection(section.value) && !isFreetextSection(section.value)) {
            return;
        }

        getSection(section.value.id).match(
            (artidoc_section: ArtidocSection) => {
                if (isArtifactSection(artidoc_section) || isFreetextSection(artidoc_section)) {
                    update_sections.updateSection(artidoc_section);
                }
                close_section_editor.closeEditor();
                section_state.is_just_refreshed.value = true;
            },
            (fault: Fault) => {
                manage_error_state.handleError(fault);
                section_state.is_outdated.value = false;
            },
        );
    },
});

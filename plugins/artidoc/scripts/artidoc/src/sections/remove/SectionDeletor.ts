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

import type { Fault } from "@tuleap/fault";
import type { RemoveSections } from "@/sections/remove/SectionsRemover";
import type { SectionState } from "@/sections/states/SectionStateBuilder";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { ManageErrorState } from "@/sections/states/SectionErrorManager";

export type DeleteSection = {
    deleteSection(): void;
};

export const getSectionDeletor = (
    section: ReactiveStoredArtidocSection,
    section_state: SectionState,
    manage_error_state: ManageErrorState,
    remove_sections: RemoveSections,
    raise_delete_section_error_callback: (error_message: string) => void,
): DeleteSection => ({
    deleteSection(): void {
        remove_sections.removeSection(section).mapErr((fault: Fault) => {
            if (section_state.is_section_in_edit_mode.value) {
                manage_error_state.handleError(fault);
            } else {
                raise_delete_section_error_callback(String(fault));
            }
        });
    },
});

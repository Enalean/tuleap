/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    InternalArtidocSectionId,
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";

export type SectionsStructurer = {
    getSectionChildren(section: InternalArtidocSectionId): ReactiveStoredArtidocSection[];
};

export const getSectionsStructurer = (
    sections_collection: SectionsCollection,
): SectionsStructurer => {
    function getSectionChildren(section: InternalArtidocSectionId): ReactiveStoredArtidocSection[] {
        const index_of_section = sections_collection.sections.value.findIndex(
            (element) => element.value.internal_id === section.internal_id,
        );
        const level = sections_collection.sections.value[index_of_section].value.level;
        const child_sections: ReactiveStoredArtidocSection[] = [];

        if (level === 3) {
            return [];
        }

        let i = 1;
        while (
            sections_collection.sections.value[index_of_section + i] &&
            sections_collection.sections.value[index_of_section + i].value.level > level
        ) {
            child_sections.push(sections_collection.sections.value[index_of_section + i]);
            i++;
        }

        return child_sections;
    }

    return {
        getSectionChildren,
    };
};

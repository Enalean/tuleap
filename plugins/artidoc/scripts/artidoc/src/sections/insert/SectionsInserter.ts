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

import { ref } from "vue";
import type {
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";
import type {
    PositionForSection,
    AtTheEnd,
} from "@/sections/save/SectionsPositionsForSaveRetriever";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isPendingSection } from "@/helpers/artidoc-section.type";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import type { NumberSections } from "@/sections/levels/SectionsNumberer";

export type InsertSections = {
    insertSection(section: ArtidocSection, position: PositionForSection): void;
};

export const AT_THE_END: AtTheEnd = null;

export const getSectionsInserter = (
    sections_collection: SectionsCollection,
    states_collection: SectionsStatesCollection,
    sections_numberer: NumberSections,
): InsertSections => {
    const NOT_FOUND = -1;

    const getIndexWhereSectionShouldBeInserted = (
        sections: ReactiveStoredArtidocSection[],
        position: PositionForSection,
    ): number => {
        if (position === AT_THE_END) {
            return NOT_FOUND;
        }

        return sections.findIndex((sibling) => sibling.value.id === position.before);
    };

    function removeExistingLonelyPendingSection(): void {
        if (sections_collection.sections.value.length !== 1) {
            return;
        }

        const existing_section = sections_collection.sections.value[0].value;
        if (!isPendingSection(existing_section)) {
            return;
        }

        const state = states_collection.getSectionState(existing_section);
        const has_changed =
            state.edited_title.value !== existing_section.title ||
            state.edited_description.value !== existing_section.description;

        if (has_changed) {
            return;
        }

        states_collection.destroySectionState(existing_section);
        sections_collection.sections.value.splice(0, 1);
    }

    return {
        insertSection(section, position): void {
            removeExistingLonelyPendingSection();

            const new_section = ref(CreateStoredSections.fromArtidocSection(section));
            states_collection.createStateForSection(new_section);

            if (position === AT_THE_END) {
                sections_collection.sections.value.push(new_section);
            } else {
                const index = getIndexWhereSectionShouldBeInserted(
                    sections_collection.sections.value,
                    position,
                );
                sections_collection.sections.value.splice(index, 0, new_section);
            }

            sections_numberer.setInsertedSectionLevel(new_section);
        },
    };
};

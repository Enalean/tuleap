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
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import {
    initLevelAccordingToPreviousSectionLevel,
    updateDisplayLevelToSections,
} from "@/sections/levels/SectionsNumberer";

export type InsertSections = {
    insertSection(section: ArtidocSection, position: PositionForSection): void;
};

export const AT_THE_END: AtTheEnd = null;

export const getSectionsInserter = (
    sections_collection: SectionsCollection,
    states_collection: SectionsStatesCollection,
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

    return {
        insertSection(section, position): void {
            const index = getIndexWhereSectionShouldBeInserted(
                sections_collection.sections.value,
                position,
            );
            const new_section = ref(CreateStoredSections.fromArtidocSection(section));
            states_collection.createStateForSection(new_section);
            if (index === NOT_FOUND) {
                sections_collection.sections.value.push(new_section);
                new_section.value.level = initLevelAccordingToPreviousSectionLevel(
                    sections_collection.sections.value,
                    sections_collection.sections.value.length - 1,
                );
                updateDisplayLevelToSections(sections_collection.sections.value);
                return;
            }
            sections_collection.sections.value.splice(index, 0, new_section);

            new_section.value.level = initLevelAccordingToPreviousSectionLevel(
                sections_collection.sections.value,
                index,
            );
            updateDisplayLevelToSections(sections_collection.sections.value);
        },
    };
};

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
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import { isFreetextSection, isSectionBasedOnArtifact } from "@/helpers/artidoc-section.type";

export type Level = 1 | 2 | 3;
export const LEVEL_1: Level = 1;
export const LEVEL_2: Level = 2;
export const LEVEL_3: Level = 3;

function setSectionsDisplayLevels(sections_collection: SectionsCollection): void {
    let level_1_counter = 0;
    let level_2_counter = 0;
    let level_3_counter = 0;

    sections_collection.sections.value.map((section) => {
        if (section.value.level === LEVEL_1) {
            level_1_counter++;
            level_2_counter = 0;
            level_3_counter = 0;

            section.value.display_level = `${level_1_counter}. `;
        } else if (section.value.level === LEVEL_2) {
            if (level_1_counter === 0) {
                level_1_counter = 1;
            }

            level_2_counter++;
            level_3_counter = 0;

            section.value.display_level = `${level_1_counter}.${level_2_counter}. `;
        } else if (section.value.level === LEVEL_3) {
            if (level_1_counter === 0) {
                level_1_counter = 1;
            }
            if (level_2_counter === 0) {
                level_2_counter = 1;
            }

            level_3_counter++;
            section.value.display_level = `${level_1_counter}.${level_2_counter}.${level_3_counter}. `;
        }
        return section;
    });
}

function getNewlyInsertedSectionLevelFromIndex(
    sections_collection: SectionsCollection,
    index: number,
): Level {
    const previous_section = sections_collection.sections.value[index - 1];
    if (!previous_section) {
        return 1;
    }
    if (
        isFreetextSection(previous_section.value) &&
        isSectionBasedOnArtifact(sections_collection.sections.value[index].value)
    ) {
        return previous_section.value.level === LEVEL_1 ? LEVEL_2 : LEVEL_3;
    }
    return previous_section.value.level;
}

export type NumberSections = {
    updateSectionsLevels(): void;
    setSectionLevel(section: ReactiveStoredArtidocSection, level: Level): void;
    setInsertedSectionLevel(inserted_section: ReactiveStoredArtidocSection): void;
    getLevelFromPositionOfImportedExistingSection(position: PositionForSection): Level;
};

export const getSectionsNumberer = (sections_collection: SectionsCollection): NumberSections => ({
    updateSectionsLevels: (): void => {
        setSectionsDisplayLevels(sections_collection);
    },
    setSectionLevel: (section, level): void => {
        section.value.level = level;

        setSectionsDisplayLevels(sections_collection);
    },
    setInsertedSectionLevel: (inserted_section): void => {
        const inserted_section_index = sections_collection.sections.value.findIndex(
            (section) => section.value.internal_id === inserted_section.value.internal_id,
        );

        if (inserted_section_index === -1) {
            throw new Error(
                `Unable to find the index of the newly inserted section #${inserted_section.value.internal_id}`,
            );
        }

        inserted_section.value.level = getNewlyInsertedSectionLevelFromIndex(
            sections_collection,
            inserted_section_index,
        );

        setSectionsDisplayLevels(sections_collection);
    },
    getLevelFromPositionOfImportedExistingSection: (position): Level => {
        const index =
            position === AT_THE_END
                ? sections_collection.sections.value.length
                : sections_collection.sections.value.findIndex(
                      (sibling) => sibling.value.id === position?.before,
                  );

        const previous_section = sections_collection.sections.value[index - 1];

        if (!previous_section) {
            return LEVEL_1;
        }

        if (isFreetextSection(previous_section.value)) {
            return previous_section.value.level === LEVEL_1 ? LEVEL_2 : LEVEL_3;
        }
        return previous_section.value.level;
    },
});

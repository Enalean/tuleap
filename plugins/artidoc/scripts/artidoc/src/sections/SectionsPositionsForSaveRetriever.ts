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

import type { SectionsCollection } from "@/sections/SectionsCollection";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isPendingSection } from "@/helpers/artidoc-section.type";

type BeforeSection = { before: string };
export type AtTheEnd = null;

export type PositionForSection = AtTheEnd | BeforeSection;

export type RetrieveSectionsPositionForSave = {
    getSectionPositionForSave(section: ArtidocSection): PositionForSection;
};

export const getSectionsPositionsForSaveRetriever = (
    sections_collection: SectionsCollection,
): RetrieveSectionsPositionForSave => ({
    getSectionPositionForSave(section): PositionForSection {
        const index = sections_collection.sections.value.findIndex(
            (element) => element.value.id === section.id,
        );
        if (index === -1) {
            return null;
        }

        if (index === sections_collection.sections.value.length - 1) {
            return null;
        }

        for (let i = index + 1; i < sections_collection.sections.value.length; i++) {
            if (!isPendingSection(sections_collection.sections.value[i].value)) {
                return { before: sections_collection.sections.value[i].value.id };
            }
        }

        return null;
    },
});

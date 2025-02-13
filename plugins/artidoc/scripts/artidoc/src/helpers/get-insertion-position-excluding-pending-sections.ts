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

import type { SectionsCollection } from "@/sections/SectionsCollection";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import { isPendingSection } from "@/helpers/artidoc-section.type";

export function getInsertionPositionExcludingPendingSections(
    add_position: PositionForSection,
    sections_collection: SectionsCollection,
): PositionForSection {
    if (add_position === AT_THE_END) {
        return add_position;
    }

    const index = sections_collection.sections.value.findIndex(
        (sibling) => sibling.value.id === add_position?.before,
    );
    if (index === -1) {
        return AT_THE_END;
    }

    let before: string | null = null;
    for (let i = index; i < sections_collection.sections.value.length; i++) {
        if (!isPendingSection(sections_collection.sections.value[i].value)) {
            before = sections_collection.sections.value[i].value.id;
            break;
        }
    }

    if (before === null) {
        return AT_THE_END;
    }

    return { before };
}

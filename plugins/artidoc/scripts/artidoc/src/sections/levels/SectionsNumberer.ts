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

import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

function setSectionsLevels(sections: ArtidocSection[]): ArtidocSection[] {
    let level1 = 0;
    let level2 = 0;
    let level3 = 0;

    return sections.map((section) => {
        if (section.level === 1) {
            level1++;
            level2 = 0;
            level3 = 0;

            section.display_level = `${level1}. `;
        } else if (section.level === 2) {
            if (level1 === 0) {
                level1 = 1;
            }

            level2++;
            level3 = 0;

            section.display_level = `${level1}.${level2}. `;
        } else if (section.level === 3) {
            if (level1 === 0) {
                level1 = 1;
            }
            if (level2 === 0) {
                level2 = 1;
            }

            level3++;
            section.display_level = `${level1}.${level2}.${level3}. `;
        }
        return section;
    });
}

export function injectDisplayLevel(sections: ArtidocSection[]): ArtidocSection[] {
    return setSectionsLevels(sections);
}

export function updateDisplayLevelToSections(sections: ReactiveStoredArtidocSection[]): void {
    setSectionsLevels(sections.map((section) => section.value));
}

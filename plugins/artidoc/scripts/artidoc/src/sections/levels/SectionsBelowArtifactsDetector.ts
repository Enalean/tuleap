/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

export interface SectionsBelowArtifactsDetector {
    detect(sections: ReactiveStoredArtidocSection[]): ReadonlyArray<string>;
}

export function buildSectionsBelowArtifactsDetector(): SectionsBelowArtifactsDetector {
    return {
        detect(sections): ReadonlyArray<string> {
            return sections.reduce(
                (accumulator: ReadonlyArray<string>, current_section, index, all_sections) => {
                    const sections_before_current = all_sections.slice(0, index);

                    const parent_section = sections_before_current.findLast(
                        (section) => section.value.level < current_section.value.level,
                    );
                    if (!parent_section) {
                        return accumulator;
                    }
                    if (parent_section.value.type === "artifact") {
                        return [...accumulator, current_section.value.internal_id];
                    }
                    return accumulator;
                },
                [],
            );
        },
    };
}

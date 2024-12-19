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
import type {
    ArtidocSection,
    ArtifactSection,
    FreetextSection,
} from "@/helpers/artidoc-section.type";

export function extractArtifactAndFreetextSectionsFromArtidocSections(
    sections: readonly ArtidocSection[] | undefined,
): ReadonlyArray<ArtifactSection | FreetextSection> | undefined {
    if (sections === undefined) {
        return undefined;
    }

    return sections.reduce(
        (saved: Array<ArtifactSection | FreetextSection>, current: ArtidocSection) => {
            if (isArtifactSection(current) || isFreetextSection(current)) {
                saved.push(current);
            }

            return saved;
        },
        [],
    );
}

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

import type {
    ArtidocSection,
    PendingArtifactSection,
    PendingFreetextSection,
} from "@/helpers/artidoc-section.type";
import type { SectionsCollection } from "@/sections/SectionsCollection";

export type ReplacePendingSections = {
    replacePendingSection(
        pending: PendingArtifactSection | PendingFreetextSection,
        section: ArtidocSection,
    ): void;
};

export const getPendingSectionsReplacer = (
    sections_collection: SectionsCollection,
): ReplacePendingSections => ({
    replacePendingSection(pending, section): void {
        const index = sections_collection.sections.value.findIndex(
            (element) => element.value.id === pending.id,
        );
        if (index === -1) {
            return;
        }

        sections_collection.sections.value[index].value = {
            ...section,
            internal_id: sections_collection.sections.value[index].value.internal_id,
        };
    },
});

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

import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { StoredArtidocSection } from "@/stores/SectionsCollection";
import { injectInternalId } from "@/helpers/inject-internal-id";

export const CreateStoredSections = {
    fromArtidocSectionsCollection: (sections: readonly ArtidocSection[]): StoredArtidocSection[] =>
        sections.map(injectInternalId),
    fromArtidocSection: (section: ArtidocSection): StoredArtidocSection =>
        injectInternalId(section),
};

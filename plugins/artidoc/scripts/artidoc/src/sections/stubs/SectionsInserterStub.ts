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

import type { InsertSections } from "@/sections/insert/SectionsInserter";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

export type InsertSectionsStub = InsertSections & {
    getLastInsertedSection(): ArtidocSection | null;
};

export const SectionsInserterStub = {
    withExpectedCall(): InsertSectionsStub {
        let last_inserted_section: ArtidocSection | null = null;

        return {
            getLastInsertedSection: () => last_inserted_section,
            insertSection(section: ArtidocSection): void {
                last_inserted_section = section;
            },
        };
    },
    withoutExpectedCall(): InsertSections {
        return {
            insertSection(): void {
                throw new Error("Did not expect SectionsInserter::insertSection to be called");
            },
        };
    },
};

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

import type { UpdateSections } from "@/sections/update/SectionsUpdater";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

export type SectionsUpdaterStub = UpdateSections & {
    getLastUpdatedSection(): ArtidocSection | null;
};

export const SectionsUpdaterStub = {
    withExpectedCall(): SectionsUpdaterStub {
        let last_updated_section: ArtidocSection | null = null;

        return {
            getLastUpdatedSection: () => last_updated_section,
            updateSection(section): void {
                last_updated_section = section;
            },
        };
    },
    withNoExpectedCall: (): UpdateSections => ({
        updateSection(): void {
            throw new Error("Did not expect SectionsUpdater::updateSection to be called");
        },
    }),
};

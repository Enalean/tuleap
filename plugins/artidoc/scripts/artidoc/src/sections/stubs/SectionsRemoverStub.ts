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

import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { RemoveSections } from "@/sections/SectionsRemover";

export type RemoveSectionsStub = RemoveSections & {
    getLastRemovedSection(): ArtidocSection | null;
};

export const SectionsRemoverStub = {
    withExpectedCall(): RemoveSectionsStub {
        let last_removed_section: ArtidocSection | null = null;

        return {
            getLastRemovedSection: () => last_removed_section,
            removeSection(section: ArtidocSection): ResultAsync<boolean, Fault> {
                last_removed_section = section;

                return okAsync(true);
            },
        };
    },
    withExpectedFault(fault: Fault): RemoveSections {
        return {
            removeSection(): ResultAsync<boolean, Fault> {
                return errAsync(fault);
            },
        };
    },
    withNoExpectedCall(): RemoveSections {
        return {
            removeSection(): ResultAsync<boolean, Fault> {
                return errAsync(
                    Fault.fromMessage("Did not expect SectionsRemover::removeSection to be called"),
                );
            },
        };
    },
};

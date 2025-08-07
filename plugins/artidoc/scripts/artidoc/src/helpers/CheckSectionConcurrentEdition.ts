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

import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isFreetextSection, isSectionBasedOnArtifact } from "@/helpers/artidoc-section.type";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { getSection } from "@/helpers/rest-querier";

export interface OutdatedSectionFault extends Fault {
    isOutdatedSectionFault: () => true;
}

export const OutdatedSectionFault = {
    build: (): OutdatedSectionFault => ({
        isOutdatedSectionFault: () => true,
        ...Fault.fromMessage("The section is outdated"),
    }),
};

export function isOutdatedSectionFault(fault: Fault): fault is OutdatedSectionFault {
    return "isOutdatedSectionFault" in fault && fault.isOutdatedSectionFault() === true;
}

export function checkSectionConcurrentEdition(
    old_section: ArtidocSection,
): ResultAsync<ArtidocSection, Fault> {
    return getSection(old_section.id).andThen(
        (new_section: ArtidocSection): ResultAsync<ArtidocSection, OutdatedSectionFault> => {
            if (
                isSectionBasedOnArtifact(new_section) &&
                isSectionBasedOnArtifact(old_section) &&
                new_section.title === old_section.title &&
                new_section.description === old_section.description
            ) {
                return okAsync(new_section);
            }
            if (
                isFreetextSection(new_section) &&
                isFreetextSection(old_section) &&
                new_section.title === old_section.title &&
                new_section.description === old_section.description
            ) {
                return okAsync(new_section);
            }

            return errAsync(OutdatedSectionFault.build());
        },
    );
}

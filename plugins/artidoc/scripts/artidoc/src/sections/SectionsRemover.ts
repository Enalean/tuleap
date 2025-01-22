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

import { okAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { deleteSection } from "@/helpers/rest-querier";
import { isPendingSection } from "@/helpers/artidoc-section.type";

export type RemoveSections = {
    removeSection: (section: ArtidocSection) => ResultAsync<boolean, Fault>;
};

export const getSectionsRemover = (sections_collection: SectionsCollection): RemoveSections => ({
    removeSection(section: ArtidocSection): ResultAsync<boolean, Fault> {
        const index = sections_collection.sections.value.findIndex(
            (element) => element.id === section.id,
        );
        if (index === -1) {
            return okAsync(true);
        }

        if (isPendingSection(section)) {
            sections_collection.sections.value.splice(index, 1);

            return okAsync(true);
        }

        return deleteSection(section.id).andThen(() => {
            sections_collection.sections.value.splice(index, 1);

            return okAsync(true);
        });
    },
});

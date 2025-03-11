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

import { errAsync, okAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type {
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";
import { deleteSection } from "@/helpers/rest-querier";
import { isPendingSection } from "@/helpers/artidoc-section.type";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";

export type RemoveSections = {
    removeSection: (section: ReactiveStoredArtidocSection) => ResultAsync<boolean, Fault>;
};

export const getSectionsRemover = (
    sections_collection: SectionsCollection,
    states_collection: SectionsStatesCollection,
): RemoveSections => ({
    removeSection(section): ResultAsync<boolean, Fault> {
        const index = sections_collection.sections.value.findIndex(
            (element) => element.value.internal_id === section.value.internal_id,
        );
        if (index === -1) {
            return errAsync(
                Fault.fromMessage(`Unable to find section #${section.value.id} in the document.`),
            );
        }

        states_collection.destroySectionState(section.value);
        if (isPendingSection(section.value)) {
            sections_collection.sections.value.splice(index, 1);
            return okAsync(true);
        }
        return deleteSection(section.value.id).andThen(() => {
            sections_collection.sections.value.splice(index, 1);
            return okAsync(true);
        });
    },
});

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

import { ref } from "vue";
import { getSectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { getSectionStateBuilder } from "@/sections/states/SectionStateBuilder";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

export const SectionsStatesCollectionStub = {
    build: (): SectionsStatesCollection =>
        getSectionsStatesCollection(getSectionStateBuilder(true, ref([]))),
    fromReactiveStoredArtifactSections: (
        sections: ReactiveStoredArtidocSection[],
    ): SectionsStatesCollection => {
        const states_collection = getSectionsStatesCollection(
            getSectionStateBuilder(true, ref([])),
        );
        states_collection.createAllSectionsStates(sections);

        return states_collection;
    },
};

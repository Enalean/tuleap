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

import type { ComputedRef, Ref } from "vue";
import { computed, ref } from "vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { extractSavedSectionsFromArtidocSections } from "@/helpers/extract-saved-sections-from-artidoc-sections";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";

export type StoredArtidocSection = ArtidocSection & InternalArtidocSectionId;
export type ReactiveStoredArtidocSection = Ref<StoredArtidocSection>;

export interface SectionsCollection {
    sections: Ref<ReactiveStoredArtidocSection[]>;
    saved_sections: ComputedRef<readonly ReactiveStoredArtidocSection[]>;
    replaceAll: (sections_collection: ReactiveStoredArtidocSection[]) => void;
}

export interface InternalArtidocSectionId {
    internal_id: string;
}

export function buildSectionsCollection(
    states_collection: SectionsStatesCollection,
): SectionsCollection {
    const sections: Ref<ReactiveStoredArtidocSection[]> = ref([]);

    function replaceAll(sections_collection: ReactiveStoredArtidocSection[]): void {
        sections.value = sections_collection.map((section) => ref(section));
        states_collection.destroyAll();
        states_collection.createAllSectionsStates(sections.value);
    }

    const saved_sections: ComputedRef<readonly ReactiveStoredArtidocSection[]> = computed(() => {
        return extractSavedSectionsFromArtidocSections(sections.value);
    });

    return {
        sections,
        saved_sections,
        replaceAll,
    };
}

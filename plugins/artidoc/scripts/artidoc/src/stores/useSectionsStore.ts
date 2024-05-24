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

import type { Ref } from "vue";
import { ref, provide } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { sectionsStoreKey } from "./sectionsStoreKey";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import { getAllSections } from "@/helpers/rest-querier";

export interface SectionsStore {
    sections: Ref<readonly ArtidocSection[] | undefined>;
    is_sections_loading: Ref<boolean>;
    loadSections: (item_id: number) => void;
    updateSection: (section: ArtidocSection) => void;
}

export function useSectionsStore(): SectionsStore {
    const skeleton_data = [
        ArtidocSectionFactory.create(),
        ArtidocSectionFactory.create(),
        ArtidocSectionFactory.create(),
    ];
    const sections: Ref<ArtidocSection[] | undefined> = ref(skeleton_data);
    const is_sections_loading = ref(true);

    function loadSections(item_id: number): void {
        getAllSections(item_id).match(
            (artidoc_sections: readonly ArtidocSection[]) => {
                sections.value = [...artidoc_sections];
                is_sections_loading.value = false;
            },
            () => {
                sections.value = undefined;
                is_sections_loading.value = false;
            },
        );
    }

    function updateSection(section: ArtidocSection): void {
        if (sections.value === undefined) {
            throw Error("Unexpected call to updateSection while there is no section");
        }

        const length = sections.value.length;
        for (let i = 0; i < length; i++) {
            if (sections.value[i].id === section.id) {
                sections.value[i] = section;
            }
        }
    }

    return {
        sections,
        is_sections_loading,
        loadSections,
        updateSection,
    };
}

let sectionsStore: SectionsStore | null = null;

export function provideSectionsStore(): SectionsStore {
    if (!sectionsStore) {
        sectionsStore = useSectionsStore();
    }
    provide(sectionsStoreKey, sectionsStore);

    return sectionsStore;
}

export function useInjectSectionsStore(): SectionsStore {
    const store = strictInject<SectionsStore>(sectionsStoreKey);
    if (!store) {
        throw new Error("No sections store provided!");
    }

    return store;
}

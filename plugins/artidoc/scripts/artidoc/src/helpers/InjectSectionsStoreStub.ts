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

import type { SectionsStore } from "@/stores/useSectionsStore";
import { ref } from "vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

const noop = (): void => {};

export const InjectedSectionsStoreStub = {
    withLoadedSections: (sections: readonly ArtidocSection[]): SectionsStore => ({
        loadSections: noop,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref(sections),
    }),
    withLoadingSections: (sections: readonly ArtidocSection[] = []): SectionsStore => ({
        loadSections: noop,
        updateSection: noop,
        is_sections_loading: ref(true),
        sections: ref(sections),
    }),
    withSectionsInError: (): SectionsStore => ({
        loadSections: noop,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref(undefined),
    }),
    withMockedLoadSections: (loadSections: (item_id: number) => void): SectionsStore => ({
        loadSections,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref([]),
    }),
};

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
import { computed, ref } from "vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { Tracker } from "@/stores/configuration-store";
import { injectInternalId } from "@/helpers/inject-internal-id";
import { extractArtifactSectionsFromArtidocSections } from "@/helpers/extract-artifact-sections-from-artidoc-sections";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { noop, promised_noop } from "@/helpers/noop";

const resultasync_noop = (): ResultAsync<boolean, Fault> => okAsync(true);

export const InjectedSectionsStoreStub = {
    withLoadedSections: (sections: readonly ArtidocSection[]): SectionsStore => ({
        replacePendingByArtifactSection: noop,
        getSectionPositionForSave: () => null,
        insertPendingArtifactSectionForEmptyDocument: noop,
        insertSection: noop,
        removeSection: resultasync_noop,
        loadSections: promised_noop,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref(sections.map(injectInternalId)),
        saved_sections: computed(() => extractArtifactSectionsFromArtidocSections(sections)),
        moveSectionUp: promised_noop,
        moveSectionDown: promised_noop,
        moveSectionBefore: promised_noop,
        moveSectionAtTheEnd: promised_noop,
    }),
    withLoadingSections: (sections: readonly ArtidocSection[] = []): SectionsStore => ({
        replacePendingByArtifactSection: noop,
        getSectionPositionForSave: () => null,
        insertPendingArtifactSectionForEmptyDocument: noop,
        insertSection: noop,
        removeSection: resultasync_noop,
        loadSections: promised_noop,
        updateSection: noop,
        is_sections_loading: ref(true),
        sections: ref(sections.map(injectInternalId)),
        saved_sections: computed(() => extractArtifactSectionsFromArtidocSections(sections)),
        moveSectionUp: promised_noop,
        moveSectionDown: promised_noop,
        moveSectionBefore: promised_noop,
        moveSectionAtTheEnd: promised_noop,
    }),
    withSectionsInError: (): SectionsStore => ({
        replacePendingByArtifactSection: noop,
        getSectionPositionForSave: () => null,
        insertPendingArtifactSectionForEmptyDocument: noop,
        insertSection: noop,
        removeSection: resultasync_noop,
        loadSections: promised_noop,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref(undefined),
        saved_sections: computed(() => undefined),
        moveSectionUp: promised_noop,
        moveSectionDown: promised_noop,
        moveSectionBefore: promised_noop,
        moveSectionAtTheEnd: promised_noop,
    }),
    withMockedLoadSections: (loadSections: (item_id: number) => Promise<void>): SectionsStore => ({
        replacePendingByArtifactSection: noop,
        getSectionPositionForSave: () => null,
        insertPendingArtifactSectionForEmptyDocument: noop,
        insertSection: noop,
        removeSection: resultasync_noop,
        loadSections,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref([]),
        saved_sections: computed(() => []),
        moveSectionUp: promised_noop,
        moveSectionDown: promised_noop,
        moveSectionBefore: promised_noop,
        moveSectionAtTheEnd: promised_noop,
    }),
    withMockedMoveSection: (
        moveSectionUp: SectionsStore["moveSectionUp"],
        moveSectionDown: SectionsStore["moveSectionDown"],
    ): SectionsStore => ({
        replacePendingByArtifactSection: noop,
        getSectionPositionForSave: () => null,
        insertPendingArtifactSectionForEmptyDocument: noop,
        insertSection: noop,
        removeSection: resultasync_noop,
        loadSections: promised_noop,
        updateSection: noop,
        is_sections_loading: ref(false),
        sections: ref([]),
        saved_sections: computed(() => []),
        moveSectionUp,
        moveSectionDown,
        moveSectionBefore: promised_noop,
        moveSectionAtTheEnd: promised_noop,
    }),
    withMockedInsertPendingArtifactSectionForEmptyDocument: (
        insertPendingArtifactSectionForEmptyDocument: (tracker: Tracker | null) => void,
    ): SectionsStore => ({
        ...InjectedSectionsStoreStub.withLoadedSections([]),
        insertPendingArtifactSectionForEmptyDocument,
    }),
};

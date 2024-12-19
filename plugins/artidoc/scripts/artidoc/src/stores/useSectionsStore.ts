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
import { okAsync } from "neverthrow";
import {
    isArtifactSection,
    isFreetextSection,
    isPendingArtifactSection,
} from "@/helpers/artidoc-section.type";
import type {
    ArtidocSection,
    PendingArtifactSection,
    ArtifactSection,
} from "@/helpers/artidoc-section.type";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { deleteSection, getAllSections } from "@/helpers/rest-querier";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { Tracker } from "@/stores/configuration-store";
import { isTrackerWithSubmittableSection } from "@/stores/configuration-store";
import type { ResultAsync } from "neverthrow";
import { injectInternalId } from "@/helpers/inject-internal-id";
import { extractArtifactAndFreetextSectionsFromArtidocSections } from "@/helpers/extract-artifact-and-freetext-sections-from-artidoc-sections";
import type { Fault } from "@tuleap/fault";

export type StoredArtidocSection = ArtidocSection & InternalArtidocSectionId;

export interface SectionsStore {
    sections: Ref<StoredArtidocSection[] | undefined>;
    saved_sections: ComputedRef<readonly ArtidocSection[] | undefined>;
    is_sections_loading: Ref<boolean>;
    loadSections: (
        item_id: number,
        tracker: Tracker | null,
        can_user_edit_document: boolean,
    ) => Promise<void>;
    updateSection: (section: ArtidocSection) => void;
    insertSection: (section: ArtidocSection, position: PositionForSection) => void;
    removeSection: (
        section: ArtidocSection,
        tracker: Tracker | null,
    ) => ResultAsync<boolean, Fault>;
    insertPendingArtifactSectionForEmptyDocument: (tracker: Tracker | null) => void;
    getSectionPositionForSave: (section: ArtidocSection) => PositionForSection;
    replacePendingByArtifactSection: (
        pending: PendingArtifactSection,
        section: ArtifactSection,
    ) => void;
}

type BeforeSection = { before: string };
export type AtTheEnd = null;

export const AT_THE_END: AtTheEnd = null;
export type PositionForSection = AtTheEnd | BeforeSection;
export interface InternalArtidocSectionId {
    internal_id: string;
}

export function useSectionsStore(): SectionsStore {
    const skeleton_data = [
        ArtifactSectionFactory.skeleton(),
        ArtifactSectionFactory.skeleton(),
        ArtifactSectionFactory.skeleton(),
    ].map(injectInternalId);

    const sections: Ref<StoredArtidocSection[] | undefined> = ref(skeleton_data);
    const is_sections_loading = ref(true);

    const saved_sections: ComputedRef<readonly ArtidocSection[] | undefined> = computed(() => {
        return extractArtifactAndFreetextSectionsFromArtidocSections(sections.value);
    });

    function loadSections(
        item_id: number,
        tracker: Tracker | null,
        can_user_edit_document: boolean,
    ): Promise<void> {
        return getAllSections(item_id)
            .andThen((artidoc_sections: readonly ArtidocSection[]) => {
                sections.value = [...artidoc_sections].map(injectInternalId);

                if (sections.value.length === 0 && can_user_edit_document) {
                    insertPendingArtifactSectionForEmptyDocument(tracker);
                }

                return okAsync(true);
            })
            .match(
                () => {
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
            const current = sections.value[i];
            if (
                (isArtifactSection(current) || isFreetextSection(current)) &&
                current.id === section.id
            ) {
                sections.value[i] = {
                    ...section,
                    internal_id: sections.value[i].internal_id,
                };
            }
        }
    }

    function insertSection(section: ArtidocSection, position: PositionForSection): void {
        if (sections.value === undefined) {
            return;
        }

        const NOT_FOUND = -1;
        const index = getIndexWhereSectionShouldBeInserted(sections.value, position);

        if (index === NOT_FOUND) {
            sections.value.push(injectInternalId(section));
        } else {
            sections.value.splice(index, 0, injectInternalId(section));
        }

        function getIndexWhereSectionShouldBeInserted(
            sections: StoredArtidocSection[],
            position: PositionForSection,
        ): number {
            if (position === AT_THE_END) {
                return NOT_FOUND;
            }

            return sections.findIndex((sibling) => sibling.id === position.before);
        }
    }

    function insertPendingArtifactSectionForEmptyDocument(tracker: Tracker | null): void {
        if (!tracker) {
            return;
        }

        if (!isTrackerWithSubmittableSection(tracker)) {
            return;
        }

        if (sections.value === undefined) {
            return;
        }
        if (sections.value.length > 0) {
            return;
        }

        sections.value.push(
            injectInternalId(PendingArtifactSectionFactory.overrideFromTracker(tracker)),
        );
    }

    function removeSection(
        section: ArtidocSection,
        tracker: Tracker | null,
    ): ResultAsync<boolean, Fault> {
        if (sections.value === undefined) {
            return okAsync(true);
        }

        const index = sections.value.findIndex((element) => element.id === section.id);
        if (index === -1) {
            return okAsync(true);
        }

        if (isPendingArtifactSection(section)) {
            sections.value.splice(index, 1);

            insertPendingArtifactSectionForEmptyDocument(tracker);

            return okAsync(true);
        }

        return deleteSection(section.id).andThen(() => {
            if (sections.value === undefined) {
                return okAsync(true);
            }

            sections.value.splice(index, 1);

            insertPendingArtifactSectionForEmptyDocument(tracker);

            return okAsync(true);
        });
    }

    function getSectionPositionForSave(section: ArtidocSection): PositionForSection {
        if (sections.value === undefined) {
            return null;
        }

        const index = sections.value.findIndex((element) => element.id === section.id);
        if (index === -1) {
            return null;
        }

        if (index === sections.value.length - 1) {
            return null;
        }

        for (let i = index + 1; i < sections.value.length; i++) {
            if (!isPendingArtifactSection(sections.value[i])) {
                return { before: sections.value[i].id };
            }
        }

        return null;
    }

    function replacePendingByArtifactSection(
        pending: PendingArtifactSection,
        section: ArtifactSection,
    ): void {
        if (sections.value === undefined) {
            return;
        }

        const index = sections.value.findIndex((element) => element.id === pending.id);
        if (index === -1) {
            return;
        }

        sections.value[index] = {
            ...section,
            internal_id: sections.value[index].internal_id,
        };
    }

    return {
        sections,
        saved_sections,
        is_sections_loading,
        loadSections,
        updateSection,
        insertSection,
        removeSection,
        insertPendingArtifactSectionForEmptyDocument,
        getSectionPositionForSave,
        replacePendingByArtifactSection,
    };
}

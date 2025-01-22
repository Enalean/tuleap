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

import { describe, it, expect, beforeEach } from "vitest";
import { ref } from "vue";
import type { Ref } from "vue";
import { flushPromises } from "@vue/test-utils";
import { isPendingArtifactSection, isPendingFreetextSection } from "@/helpers/artidoc-section.type";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { Tracker } from "@/stores/configuration-store";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { watchForNeededPendingSectionInsertion } from "@/sections/PendingSectionInserter";
import { CreateStoredSections } from "@/sections/CreateStoredSections";

describe("PendingSectionInserter", () => {
    let sections_collection: SectionsCollection,
        selected_tracker: Ref<Tracker | null>,
        can_user_edit_document: boolean;

    beforeEach(() => {
        sections_collection = buildSectionsCollection();
        sections_collection.replaceAll(
            CreateStoredSections.fromArtidocSectionsCollection([ArtifactSectionFactory.create()]),
        );

        selected_tracker = ref(null);
        can_user_edit_document = true;
    });

    const watchAndInsertPendingSectionIfNeeded = (): void => {
        watchForNeededPendingSectionInsertion(
            sections_collection,
            ref(selected_tracker),
            can_user_edit_document,
        );
    };

    const removeAllDocumentSections = (): Promise<unknown> => {
        sections_collection.replaceAll([]);
        return flushPromises();
    };

    it(`Given a document that:
        - is configured
        - has one section
        And a user that:
        - can edit the document
        - can submit artifacts in the selected tracker
        When the document is emptied
        Then it should insert a pending artifact section`, async () => {
        selected_tracker.value = TrackerStub.withTitleAndDescription();

        watchAndInsertPendingSectionIfNeeded();

        await removeAllDocumentSections();

        expect(sections_collection.sections.value).toHaveLength(1);
        expect(isPendingArtifactSection(sections_collection.sections.value[0])).toBe(true);
    });

    it(`Given a document that:
        - is configured
        - has one section
        And a user that:
        - can edit the document
        - CANNOT submit the title field of the selected tracker
        When the document is emptied
        Then it should insert a pending freetext section`, async () => {
        selected_tracker.value = TrackerStub.withDescription();

        watchAndInsertPendingSectionIfNeeded();

        await removeAllDocumentSections();

        expect(sections_collection.sections.value).toHaveLength(1);
        expect(isPendingFreetextSection(sections_collection.sections.value[0])).toBe(true);
    });

    it(`Given a document that:
        - is configured
        - has one section
        And a user that:
        - can edit the document
        - CANNOT submit the description field of the selected tracker
        When the document is emptied
        Then it should insert a pending freetext section`, async () => {
        selected_tracker.value = TrackerStub.withTitle();

        watchAndInsertPendingSectionIfNeeded();

        await removeAllDocumentSections();

        expect(sections_collection.sections.value).toHaveLength(1);
        expect(isPendingFreetextSection(sections_collection.sections.value[0])).toBe(true);
    });

    it(`Given a document that:
        - is configured
        - has one section
        And a user that:
        - can edit the document
        - CANNOT submit the title nor the description field of the selected tracker
        When the document is emptied
        Then it should insert a pending freetext section`, async () => {
        selected_tracker.value = TrackerStub.withoutTitleAndDescription();

        watchAndInsertPendingSectionIfNeeded();

        await removeAllDocumentSections();

        expect(sections_collection.sections.value).toHaveLength(1);
        expect(isPendingFreetextSection(sections_collection.sections.value[0])).toBe(true);
    });

    it(`Given a document that:
        - is configured
        - has one section
        And a user that:
        - can edit the document
        - can submit artifacts in the selected tracker
        When a section is added
        Then it should not insert a pending artifact section`, async () => {
        selected_tracker.value = TrackerStub.withTitleAndDescription();

        watchAndInsertPendingSectionIfNeeded();

        sections_collection.replaceAll(
            CreateStoredSections.fromArtidocSectionsCollection([
                ...sections_collection.sections.value,
                ArtifactSectionFactory.create(),
            ]),
        );
        await flushPromises();

        expect(sections_collection.sections.value).toHaveLength(2);
    });

    it(`Given an empty and not configured document
        When the tracker has been selected and the user can submit its title and description fields
        Then it should insert a pending artifact section`, async () => {
        sections_collection.replaceAll([]);

        watchAndInsertPendingSectionIfNeeded();

        selected_tracker.value = TrackerStub.withTitleAndDescription();
        await flushPromises();

        expect(sections_collection.sections.value).toHaveLength(1);
        expect(isPendingArtifactSection(sections_collection.sections.value[0])).toBe(true);
    });
});

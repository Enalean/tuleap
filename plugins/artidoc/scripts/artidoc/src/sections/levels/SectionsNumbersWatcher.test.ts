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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { MockInstance } from "vitest";
import { flushPromises } from "@vue/test-utils";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { getSectionsNumberer, LEVEL_2 } from "@/sections/levels/SectionsNumberer";
import { watchUpdateSectionsLevels } from "@/sections/levels/SectionsNumbersWatcher";

describe("SectionsNumbersWatcher", () => {
    let sections_collection: SectionsCollection, updateSectionsLevels: MockInstance;

    beforeEach(() => {
        sections_collection = SectionsCollectionStub.withSections([
            FreetextSectionFactory.create(),
            FreetextSectionFactory.pending(),
            ArtifactSectionFactory.create(),
            PendingArtifactSectionFactory.create(),
        ]);

        const sections_numberer = getSectionsNumberer(sections_collection);

        updateSectionsLevels = vi.spyOn(sections_numberer, "updateSectionsLevels");
        watchUpdateSectionsLevels(sections_collection, sections_numberer);

        vi.resetAllMocks();
    });

    it("When a section is removed from the collection, then it should update the levels of the sections ", async () => {
        sections_collection.sections.value.splice(0, 1);

        await flushPromises();
        expect(updateSectionsLevels).toHaveBeenCalled();
    });

    it("When the level of a section is updated, then it should update the levels of the sections ", async () => {
        sections_collection.sections.value[1].value.level = LEVEL_2;

        await flushPromises();
        expect(updateSectionsLevels).toHaveBeenCalled();
    });

    it("When a section is added to the collection, then it should update the levels of the sections ", async () => {
        sections_collection.sections.value.push(
            ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.pending()),
        );

        await flushPromises();
        expect(updateSectionsLevels).toHaveBeenCalled();
    });
});

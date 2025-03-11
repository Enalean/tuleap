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
import { okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import * as rest from "@/helpers/rest-querier";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { getSectionsRemover } from "@/sections/remove/SectionsRemover";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";

const section1 = ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create());
const section2 = ReactiveStoredArtidocSectionStub.fromSection(
    PendingArtifactSectionFactory.create(),
);
const section3 = ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create());
const section4 = ReactiveStoredArtidocSectionStub.fromSection(
    PendingArtifactSectionFactory.create(),
);

describe("SectionsRemover", () => {
    let sections_collection: SectionsCollection, states_collection: SectionsStatesCollection;

    beforeEach(() => {
        states_collection = SectionsStatesCollectionStub.build();
        sections_collection = buildSectionsCollection(states_collection);
        sections_collection.replaceAll([section1, section2, section3, section4]);
    });

    it("should remove the section and delete its state", async () => {
        vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

        const sections_remover = getSectionsRemover(sections_collection, states_collection);

        sections_remover.removeSection(section2);
        sections_remover.removeSection(section3);
        await flushPromises();

        expect(sections_collection.sections.value).not.toBeUndefined();
        expect(sections_collection.sections.value).toHaveLength(2);
        expect(sections_collection.sections.value[0].value.id).toBe(section1.value.id);
        expect(sections_collection.sections.value[1].value.id).toBe(section4.value.id);

        expect(() => states_collection.getSectionState(section2.value)).toThrow();
        expect(() => states_collection.getSectionState(section3.value)).toThrow();
    });

    it("should do nothing when there is no sections", async () => {
        sections_collection.replaceAll([]);

        const sections_remover = getSectionsRemover(sections_collection, states_collection);

        sections_remover.removeSection(
            ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create()),
        );
        await flushPromises();

        expect(sections_collection.sections.value).toHaveLength(0);
    });

    it("should do nothing when section cannot be found", async () => {
        const sections_remover = getSectionsRemover(sections_collection, states_collection);

        sections_remover.removeSection(
            ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create()),
        );
        await flushPromises();

        expect(sections_collection.sections.value).toHaveLength(4);
        expect(sections_collection.sections.value[0].value.id).toBe(section1.value.id);
        expect(sections_collection.sections.value[1].value.id).toBe(section2.value.id);
        expect(sections_collection.sections.value[2].value.id).toBe(section3.value.id);
        expect(sections_collection.sections.value[3].value.id).toBe(section4.value.id);
    });
});

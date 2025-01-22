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
import * as rest from "@/helpers/rest-querier";
import { okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { CreateStoredSections } from "@/sections/CreateStoredSections";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { getSectionsRemover } from "@/sections/SectionsRemover";

const section1 = ArtifactSectionFactory.create();
const section2 = PendingArtifactSectionFactory.create();
const section3 = ArtifactSectionFactory.create();
const section4 = PendingArtifactSectionFactory.create();

describe("SectionsRemover", () => {
    let sections_collection: SectionsCollection;

    beforeEach(() => {
        sections_collection = buildSectionsCollection();
        sections_collection.replaceAll(
            CreateStoredSections.fromArtidocSectionsCollection([
                section1,
                section2,
                section3,
                section4,
            ]),
        );
    });

    it("should remove the section", async () => {
        vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

        const sections_remover = getSectionsRemover(sections_collection);

        sections_remover.removeSection(section2);
        sections_remover.removeSection(section3);
        await flushPromises();

        expect(sections_collection.sections.value).not.toBeUndefined();
        expect(sections_collection.sections.value).toHaveLength(2);
        expect(sections_collection.sections.value[0].id).toBe(section1.id);
        expect(sections_collection.sections.value[1].id).toBe(section4.id);
    });

    it("should do nothing when there is no sections", async () => {
        sections_collection.replaceAll([]);

        const sections_remover = getSectionsRemover(sections_collection);

        sections_remover.removeSection(ArtifactSectionFactory.create());
        await flushPromises();

        expect(sections_collection.sections.value).toHaveLength(0);
    });

    it("should do nothing when section cannot be found", async () => {
        const sections_remover = getSectionsRemover(sections_collection);

        sections_remover.removeSection(ArtifactSectionFactory.create());
        await flushPromises();

        expect(sections_collection.sections.value).toHaveLength(4);
        expect(sections_collection.sections.value[0].id).toBe(section1.id);
        expect(sections_collection.sections.value[1].id).toBe(section2.id);
        expect(sections_collection.sections.value[2].id).toBe(section3.id);
        expect(sections_collection.sections.value[3].id).toBe(section4.id);
    });
});

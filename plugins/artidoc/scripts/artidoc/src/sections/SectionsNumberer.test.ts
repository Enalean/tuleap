/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { buildSectionsReorderer } from "@/sections/SectionsReorderer";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { updateDisplayLevelToSections } from "@/sections/SectionsNumberer";
import * as rest from "@/helpers/rest-querier";

const section_requirements = ReactiveStoredArtidocSectionStub.fromSection(
    FreetextSectionFactory.override({ display_title: "Requirements", level: 1 }),
);
const section_radiation = ReactiveStoredArtidocSectionStub.fromSection(
    PendingArtifactSectionFactory.override({ display_title: "Radiation", level: 1 }),
);
const section_advanced = ReactiveStoredArtidocSectionStub.fromSection(
    ArtifactSectionFactory.override({ display_title: "Advanced spacecraft", level: 2 }),
);
const section_life = ReactiveStoredArtidocSectionStub.fromSection(
    ArtifactSectionFactory.override({ display_title: "Life support", level: 3 }),
);
const section_random = ReactiveStoredArtidocSectionStub.fromSection(
    FreetextSectionFactory.override({
        ...FreetextSectionFactory.pending(),
        display_title: "Random stuff",
        level: 1,
    }),
);

const sections_collection = SectionsCollectionStub.fromReactiveStoredArtifactSections([
    section_requirements,
    section_radiation,
    section_advanced,
    section_life,
    section_random,
]);
const reorderer = buildSectionsReorderer(sections_collection);
describe("SectionsNumberer", () => {
    beforeEach(() => {
        vi.spyOn(rest, "reorderSections").mockReturnValue(okAsync({} as Response));
    });

    it("should adjust the section numbers according to their level", () => {
        updateDisplayLevelToSections(sections_collection.sections.value);
        expect(
            sections_collection.sections.value.map((section) => [
                section.value.display_level,
                section.value.display_title,
            ]),
        ).toStrictEqual([
            ["1. ", "Requirements"],
            ["2. ", "Radiation"],
            ["2.1. ", "Advanced spacecraft"],
            ["2.1.1. ", "Life support"],
            ["3. ", "Random stuff"],
        ]);
    });

    describe("Reorder section does not change the level of sections", () => {
        it("should adjust section numbers when a level 3 section is moved to bottom", async () => {
            await reorderer.moveSectionAtTheEnd(101, section_life.value);
            updateDisplayLevelToSections(sections_collection.sections.value);
            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.display_title,
                ]),
            ).toStrictEqual([
                ["1. ", "Requirements"],
                ["2. ", "Radiation"],
                ["2.1. ", "Advanced spacecraft"],
                ["3. ", "Random stuff"],
                ["3.1.1. ", "Life support"],
            ]);
        });

        it("should adjust section numbers when a level 3 section is moved to top", async () => {
            await reorderer.moveSectionBefore(101, section_life.value, section_requirements.value);
            updateDisplayLevelToSections(sections_collection.sections.value);
            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.display_title,
                ]),
            ).toStrictEqual([
                ["1.1.1. ", "Life support"],
                ["2. ", "Requirements"],
                ["3. ", "Radiation"],
                ["3.1. ", "Advanced spacecraft"],
                ["4. ", "Random stuff"],
            ]);
        });

        it("should adjust section numbers when a level 1 section is moved to top", async () => {
            await reorderer.moveSectionBefore(101, section_radiation.value, section_life.value);
            updateDisplayLevelToSections(sections_collection.sections.value);
            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.display_title,
                ]),
            ).toStrictEqual([
                ["1. ", "Radiation"],
                ["1.1.1. ", "Life support"],
                ["2. ", "Requirements"],
                ["2.1. ", "Advanced spacecraft"],
                ["3. ", "Random stuff"],
            ]);
        });

        it("should adjust section numbers when a level 3 section is moved down", async () => {
            await reorderer.moveSectionDown(101, section_life);
            updateDisplayLevelToSections(sections_collection.sections.value);
            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.display_title,
                ]),
            ).toStrictEqual([
                ["1. ", "Radiation"],
                ["2. ", "Requirements"],
                ["2.1.1. ", "Life support"],
                ["2.2. ", "Advanced spacecraft"],
                ["3. ", "Random stuff"],
            ]);
        });

        it("should adjust section numbers when a level 2 section is moved down", async () => {
            await reorderer.moveSectionDown(101, section_advanced);
            updateDisplayLevelToSections(sections_collection.sections.value);
            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.display_title,
                ]),
            ).toStrictEqual([
                ["1. ", "Radiation"],
                ["2. ", "Requirements"],
                ["2.1.1. ", "Life support"],
                ["3. ", "Random stuff"],
                ["3.1. ", "Advanced spacecraft"],
            ]);
        });

        it("should adjust section numbers when a level 2 section is moved to top", async () => {
            await reorderer.moveSectionBefore(101, section_advanced.value, section_radiation.value);
            updateDisplayLevelToSections(sections_collection.sections.value);
            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.display_title,
                ]),
            ).toStrictEqual([
                ["1.1. ", "Advanced spacecraft"],
                ["2. ", "Radiation"],
                ["3. ", "Requirements"],
                ["3.1.1. ", "Life support"],
                ["4. ", "Random stuff"],
            ]);
        });
    });
});

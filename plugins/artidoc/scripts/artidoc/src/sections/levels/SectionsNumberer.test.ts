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
import { buildSectionsReorderer } from "@/sections/reorder/SectionsReorderer";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import {
    initLevelAccordingToPreviousSectionLevelForImportExistingArtifactSection,
    updateDisplayLevelToSections,
} from "@/sections/levels/SectionsNumberer";
import * as rest from "@/helpers/rest-querier";
import { createSectionFromExistingArtifact } from "@/helpers/rest-querier";
import { AT_THE_END, getSectionsInserter } from "@/sections/insert/SectionsInserter";
import type { InsertSections } from "@/sections/insert/SectionsInserter";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";

const section_requirements = ReactiveStoredArtidocSectionStub.fromSection(
    FreetextSectionFactory.override({ title: "Requirements", level: 1 }),
);
const section_radiation = ReactiveStoredArtidocSectionStub.fromSection(
    PendingArtifactSectionFactory.override({ title: "Radiation", level: 1 }),
);
const section_advanced = ReactiveStoredArtidocSectionStub.fromSection(
    ArtifactSectionFactory.override({ title: "Advanced spacecraft", level: 2 }),
);
const section_life = ReactiveStoredArtidocSectionStub.fromSection(
    ArtifactSectionFactory.override({ title: "Life support", level: 3 }),
);
const section_random = ReactiveStoredArtidocSectionStub.fromSection(
    FreetextSectionFactory.override({
        ...FreetextSectionFactory.pending(),
        title: "Random stuff",
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
    let inserter: InsertSections, states_collection: SectionsStatesCollection;

    beforeEach(() => {
        states_collection = SectionsStatesCollectionStub.build();
        vi.spyOn(rest, "reorderSections").mockReturnValue(okAsync({} as Response));
        inserter = getSectionsInserter(sections_collection, states_collection);
    });

    it("should adjust the section numbers according to their level", () => {
        updateDisplayLevelToSections(sections_collection.sections.value);
        expect(
            sections_collection.sections.value.map((section) => [
                section.value.display_level,
                section.value.title,
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
                    section.value.title,
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
                    section.value.title,
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
                    section.value.title,
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
                    section.value.title,
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
                    section.value.title,
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
                    section.value.title,
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

    describe("Create a new freetext section must follow the level of the previous section", () => {
        const new_freetext_section = FreetextSectionFactory.override({
            ...FreetextSectionFactory.pending(),
            title: "Technologies section",
        });

        beforeEach(() => {
            sections_collection.sections.value = [
                section_requirements,
                section_radiation,
                section_advanced,
                section_life,
                section_random,
            ];
        });
        it("should create a section with level 1 when section is inserted at the beginning", () => {
            inserter.insertSection(new_freetext_section, { before: section_requirements.value.id });

            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.title,
                ]),
            ).toStrictEqual([
                ["1. ", "Technologies section"],
                ["2. ", "Requirements"],
                ["3. ", "Radiation"],
                ["3.1. ", "Advanced spacecraft"],
                ["3.1.1. ", "Life support"],
                ["4. ", "Random stuff"],
            ]);
        });

        it("should create a section with level 1 if the previous section is level 1", () => {
            inserter.insertSection(new_freetext_section, { before: section_radiation.value.id });

            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.title,
                ]),
            ).toStrictEqual([
                ["1. ", "Requirements"],
                ["2. ", "Technologies section"],
                ["3. ", "Radiation"],
                ["3.1. ", "Advanced spacecraft"],
                ["3.1.1. ", "Life support"],
                ["4. ", "Random stuff"],
            ]);
        });

        it("should create a section with level 2 if the previous section is level 2", () => {
            inserter.insertSection(new_freetext_section, { before: section_life.value.id });

            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.title,
                ]),
            ).toStrictEqual([
                ["1. ", "Requirements"],
                ["2. ", "Radiation"],
                ["2.1. ", "Advanced spacecraft"],
                ["2.2. ", "Technologies section"],
                ["2.2.1. ", "Life support"],
                ["3. ", "Random stuff"],
            ]);
        });

        it("should create a section with level 3 if the previous section is level 3", () => {
            inserter.insertSection(new_freetext_section, { before: section_random.value.id });

            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.title,
                ]),
            ).toStrictEqual([
                ["1. ", "Requirements"],
                ["2. ", "Radiation"],
                ["2.1. ", "Advanced spacecraft"],
                ["2.1.1. ", "Life support"],
                ["2.1.2. ", "Technologies section"],
                ["3. ", "Random stuff"],
            ]);
        });

        it("should create a section with the same level as the previous section when section is inserted at the end", () => {
            inserter.insertSection(new_freetext_section, AT_THE_END);

            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.title,
                ]),
            ).toStrictEqual([
                ["1. ", "Requirements"],
                ["2. ", "Radiation"],
                ["2.1. ", "Advanced spacecraft"],
                ["2.1.1. ", "Life support"],
                ["3. ", "Random stuff"],
                ["4. ", "Technologies section"],
            ]);
        });
    });

    describe("Create a new artifact section", () => {
        const new_artidoc_section = PendingArtifactSectionFactory.create();

        beforeEach(() => {
            sections_collection.sections.value = [
                section_requirements,
                section_radiation,
                section_advanced,
                section_life,
                section_random,
            ];
        });

        it("should create a section with level 1 when section is inserted at the beginning", () => {
            inserter.insertSection(new_artidoc_section, { before: section_requirements.value.id });

            expect(
                sections_collection.sections.value.map((section) => [
                    section.value.display_level,
                    section.value.title,
                ]),
            ).toStrictEqual([
                ["1. ", "Technologies section"],
                ["2. ", "Requirements"],
                ["3. ", "Radiation"],
                ["3.1. ", "Advanced spacecraft"],
                ["3.1.1. ", "Life support"],
                ["4. ", "Random stuff"],
            ]);
        });

        describe("after an artifact section", () => {
            it("should create a section with the same level as the previous section", () => {
                inserter.insertSection(new_artidoc_section, { before: section_life.value.id });

                expect(
                    sections_collection.sections.value.map((section) => [
                        section.value.display_level,
                        section.value.title,
                    ]),
                ).toStrictEqual([
                    ["1. ", "Requirements"],
                    ["2. ", "Radiation"],
                    ["2.1. ", "Advanced spacecraft"],
                    ["2.2. ", "Technologies section"],
                    ["2.2.1. ", "Life support"],
                    ["3. ", "Random stuff"],
                ]);
            });
        });

        describe("after a freetext section", () => {
            it("should create a section with a higher level than the previous freetext section", () => {
                inserter.insertSection(new_artidoc_section, { before: section_radiation.value.id });

                expect(
                    sections_collection.sections.value.map((section) => [
                        section.value.display_level,
                        section.value.title,
                    ]),
                ).toStrictEqual([
                    ["1. ", "Requirements"],
                    ["1.1. ", "Technologies section"],
                    ["2. ", "Radiation"],
                    ["2.1. ", "Advanced spacecraft"],
                    ["2.1.1. ", "Life support"],
                    ["3. ", "Random stuff"],
                ]);
            });
        });
    });

    describe("Create a new section from an existing artifact", () => {
        const artidoc_id = 101;
        const artifact_id = 456;

        beforeEach(() => {
            sections_collection.sections.value = [
                section_radiation,
                section_requirements,
                section_random,
            ];
        });

        function importExistingArtifact(position: PositionForSection): void {
            createSectionFromExistingArtifact(
                artidoc_id,
                artifact_id,
                position,
                initLevelAccordingToPreviousSectionLevelForImportExistingArtifactSection(
                    sections_collection.sections.value,
                    position,
                ),
            );
        }

        it("should create a section with level 1, when it is added at the very top", () => {
            const spy = vi.spyOn(rest, "createSectionFromExistingArtifact");

            const before_section_radiation = { before: section_radiation.value.id };
            importExistingArtifact(before_section_radiation);

            expect(spy).toHaveBeenCalledWith(artidoc_id, artifact_id, before_section_radiation, 1);
        });

        it("should create a section with level 1, when it is added after a level 1 artifact section", () => {
            const spy = vi.spyOn(rest, "createSectionFromExistingArtifact");

            const before_section_requirements = { before: section_requirements.value.id };
            importExistingArtifact(before_section_requirements);

            expect(spy).toHaveBeenCalledWith(
                artidoc_id,
                artifact_id,
                before_section_requirements,
                1,
            );
        });

        it("should create a section with level 2, when it is added after a level 1 freetext section", () => {
            const spy = vi.spyOn(rest, "createSectionFromExistingArtifact");

            const before_section_random = { before: section_random.value.id };
            importExistingArtifact(before_section_random);

            expect(spy).toHaveBeenCalledWith(artidoc_id, artifact_id, before_section_random, 2);
        });

        it("should create a section with level 2, when it is added at the end, and after a level 1 freetext section", () => {
            const spy = vi.spyOn(rest, "createSectionFromExistingArtifact");

            importExistingArtifact(AT_THE_END);

            expect(spy).toHaveBeenCalledWith(artidoc_id, artifact_id, AT_THE_END, 2);
        });
    });
});

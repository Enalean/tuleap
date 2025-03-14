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

import { describe, beforeEach, it, expect } from "vitest";

import type {
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import type { SectionsStructurer } from "@/sections/reorder/SectionsStructurer";
import { getSectionsStructurer } from "@/sections/reorder/SectionsStructurer";

describe("SectionBlockMover", () => {
    let sections_structurer: SectionsStructurer;
    let sections_collection: SectionsCollection;

    let section_A: ReactiveStoredArtidocSection;
    let section_AA: ReactiveStoredArtidocSection;
    let section_B: ReactiveStoredArtidocSection;
    let section_BB: ReactiveStoredArtidocSection;
    let section_BBB: ReactiveStoredArtidocSection;
    let section_BB2: ReactiveStoredArtidocSection;
    let section_C: ReactiveStoredArtidocSection;

    beforeEach(() => {
        section_A = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ title: "A", level: 1 }),
        );
        section_AA = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "AA", level: 2 }),
        );
        section_B = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ title: "B", level: 1 }),
        );
        section_BB = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "BB", level: 2 }),
        );
        section_BBB = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "BBB", level: 3 }),
        );
        section_BB2 = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "BB2", level: 2 }),
        );
        section_C = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ title: "C", level: 1 }),
        );

        sections_collection = SectionsCollectionStub.fromReactiveStoredArtifactSections([
            section_A,
            section_AA,
            section_B,
            section_BB,
            section_BBB,
            section_BB2,
            section_C,
        ]);

        sections_structurer = getSectionsStructurer(sections_collection);
    });

    describe("getSectionChildren", () => {
        it("When the section is level 1 and has a child, then it should return it", () => {
            expect(sections_structurer.getSectionChildren(section_A.value)).toEqual([section_AA]);
        });

        it("When the section is level 1 and has children, then it should return them", () => {
            expect(sections_structurer.getSectionChildren(section_B.value)).toEqual([
                section_BB,
                section_BBB,
                section_BB2,
            ]);
        });

        it("When the section is level 1 and has no children, then it should return nothing", () => {
            expect(sections_structurer.getSectionChildren(section_C.value)).toEqual([]);
        });

        it("When the section is level 2 and has a child, then it should return it", () => {
            expect(sections_structurer.getSectionChildren(section_BB.value)).toEqual([section_BBB]);
        });

        it("When the section is level 2 and has no children, then it should return nothing", () => {
            expect(sections_structurer.getSectionChildren(section_AA.value)).toEqual([]);
        });

        it("When the section is level 3, then it should return nothing", () => {
            expect(sections_structurer.getSectionChildren(section_BBB.value)).toEqual([]);
        });
    });
});

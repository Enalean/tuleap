/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import * as rest from "@/helpers/rest-querier";
import type {
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";
import { buildSectionsReorderer } from "@/sections/reorder/SectionsReorderer";
import type { SectionsReorderer } from "@/sections/reorder/SectionsReorderer";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";

describe("SectionsReorderer", () => {
    let reorderer: SectionsReorderer;
    let sections_collection: SectionsCollection;
    let stored_section0: ReactiveStoredArtidocSection;
    let stored_section1: ReactiveStoredArtidocSection;
    let stored_section2: ReactiveStoredArtidocSection;
    let stored_section2_2: ReactiveStoredArtidocSection;
    let stored_section2_2_2: ReactiveStoredArtidocSection;
    let stored_section3: ReactiveStoredArtidocSection;
    let stored_section4: ReactiveStoredArtidocSection;
    let reorder: MockInstance;

    beforeEach(() => {
        stored_section0 = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ title: "A" }),
        );
        stored_section1 = ReactiveStoredArtidocSectionStub.fromSection(
            PendingArtifactSectionFactory.override({ title: "B" }),
        );
        stored_section2 = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "C" }),
        );
        stored_section2_2 = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "CC", level: 2 }),
        );
        stored_section2_2_2 = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "CCC", level: 3 }),
        );
        stored_section3 = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ title: "D" }),
        );
        stored_section4 = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({
                ...FreetextSectionFactory.pending(),
                title: "E",
            }),
        );

        reorder = vi.spyOn(rest, "reorderSections").mockReturnValue(okAsync({} as Response));
        sections_collection = SectionsCollectionStub.fromReactiveStoredArtifactSections([
            stored_section0,
            stored_section1,
            stored_section2,
            stored_section2_2,
            stored_section2_2_2,
            stored_section3,
            stored_section4,
        ]);
        reorderer = buildSectionsReorderer(sections_collection);
    });

    describe("moveSectionUp", () => {
        it("should do nothing if the section is already at the top", async () => {
            await reorderer.moveSectionUp(101, stored_section0);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending artifact section up", async () => {
            await reorderer.moveSectionUp(101, stored_section1);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["B", "A", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending freetext section up", async () => {
            await reorderer.moveSectionUp(101, stored_section4);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "E", "D"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section up with its children", async () => {
            await reorderer.moveSectionUp(101, stored_section2);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "C", "CC", "CCC", "B", "D", "E"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.value.id,
                "before",
                stored_section3.value.id,
            );
        });

        it("should move an artifact section up with its children and call reorder if it is above an artifact section", async () => {
            await reorderer.moveSectionUp(101, stored_section2);
            await reorderer.moveSectionUp(101, stored_section2);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["C", "CC", "CCC", "A", "B", "D", "E"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.value.id,
                "before",
                stored_section0.value.id,
            );
        });

        it("When an error occurred, then it should not reorder the sections and return a Fault", async () => {
            const fault = Fault.fromMessage("Great Scott!");
            reorder.mockReturnValue(errAsync(fault));

            const result = await reorderer.moveSectionUp(101, stored_section3);
            if (result.isOk()) {
                throw new Error("Expected an error");
            }

            expect(result.error).toBe(fault);
            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
        });
    });

    describe("moveSectionDown", () => {
        it("should do nothing if the section is already at the bottom", async () => {
            await reorderer.moveSectionDown(101, stored_section4);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending artifact section down", async () => {
            await reorderer.moveSectionDown(101, stored_section1);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "C", "B", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending freetext section down", async () => {
            await reorderer.moveSectionUp(101, stored_section4);
            await reorderer.moveSectionDown(101, stored_section4);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section down", async () => {
            await reorderer.moveSectionDown(101, stored_section0);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["B", "A", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section down and call reorder if it is below an artifact section", async () => {
            await reorderer.moveSectionDown(101, stored_section0);
            await reorderer.moveSectionDown(101, stored_section0);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["B", "C", "A", "CC", "CCC", "D", "E"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section0.value.id,
                "after",
                stored_section2.value.id,
            );
        });

        it("When an error occurred, then it should not reorder the sections and return a Fault", async () => {
            const fault = Fault.fromMessage("Great Scott!");
            reorder.mockReturnValue(errAsync(fault));

            const result = await reorderer.moveSectionDown(101, stored_section2);
            if (result.isOk()) {
                throw new Error("Expected an error");
            }

            expect(result.error).toBe(fault);
            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
        });
    });

    describe("moveSectionBefore", () => {
        it("should do nothing if the section is moved at the same place", async () => {
            await reorderer.moveSectionBefore(101, stored_section1.value, stored_section2.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a section with its children before a pending artifact section", async () => {
            await reorderer.moveSectionBefore(101, stored_section2.value, stored_section1.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "C", "CC", "CCC", "B", "D", "E"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.value.id,
                "before",
                stored_section3.value.id,
            );
        });

        it("should move a section before a pending freetext section", async () => {
            await reorderer.moveSectionBefore(101, stored_section2.value, stored_section4.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "D", "C", "CC", "CCC", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a section before an artifact section", async () => {
            await reorderer.moveSectionBefore(101, stored_section2.value, stored_section0.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["C", "CC", "CCC", "A", "B", "D", "E"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.value.id,
                "before",
                stored_section0.value.id,
            );
        });

        it("should not move, when a section is move into its children", async () => {
            await reorderer.moveSectionBefore(
                101,
                stored_section2.value,
                stored_section2_2_2.value,
            );

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.value.id,
                "before",
                stored_section2_2_2.value.id,
            );
        });

        it("A before C should move ABCD to BACD", async () => {
            await reorderer.moveSectionBefore(101, stored_section0.value, stored_section2.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["B", "A", "C", "CC", "CCC", "D", "E"]);

            expect(reorder).toHaveBeenCalledOnce();
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section0.value.id,
                "before",
                stored_section2.value.id,
            );
        });

        it("When an error occurred, then it should not reorder the sections and return a Fault", async () => {
            const fault = Fault.fromMessage("Great Scott!");
            reorder.mockReturnValue(errAsync(fault));

            const result = await reorderer.moveSectionBefore(
                101,
                stored_section0.value,
                stored_section2.value,
            );
            if (result.isOk()) {
                throw new Error("Expected an error");
            }

            expect(result.error).toBe(fault);
            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
        });
    });

    describe("moveSectionAtTheEnd", () => {
        it("should do nothing if the section is moved at the same place", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section4.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending artifact section at the end", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section1.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "C", "CC", "CCC", "D", "E", "B"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending freetext section at the end", async () => {
            await reorderer.moveSectionBefore(101, stored_section4.value, stored_section0.value);
            await reorderer.moveSectionAtTheEnd(101, stored_section4.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section at the end", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section0.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["B", "C", "CC", "CCC", "D", "E", "A"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section0.value.id,
                "after",
                stored_section3.value.id,
            );
        });

        it("should move an artifact section with its children at the end", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section2.value);

            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "D", "E", "C", "CC", "CCC"]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.value.id,
                "after",
                stored_section3.value.id,
            );
        });

        it("When an error occurred, then it should not reorder the sections and return a Fault", async () => {
            const fault = Fault.fromMessage("Great Scott!");
            reorder.mockReturnValue(errAsync(fault));

            const result = await reorderer.moveSectionAtTheEnd(101, stored_section0.value);
            if (result.isOk()) {
                throw new Error("Expected an error");
            }

            expect(result.error).toBe(fault);
            expect(
                sections_collection.sections.value.map((section) => section.value.title),
            ).toStrictEqual(["A", "B", "C", "CC", "CCC", "D", "E"]);
        });
    });
});

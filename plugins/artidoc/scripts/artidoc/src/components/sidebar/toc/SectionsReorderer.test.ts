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
import type { Ref } from "vue";
import { ref } from "vue";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { injectInternalId } from "@/helpers/inject-internal-id";
import * as rest from "@/helpers/rest-querier";
import type { StoredArtidocSection } from "@/stores/useSectionsStore";
import { buildSectionsReorderer } from "@/components/sidebar/toc/SectionsReorderer";
import type { SectionsReorderer } from "@/components/sidebar/toc/SectionsReorderer";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

describe("SectionsReorderer", () => {
    let reorderer: SectionsReorderer;
    let sections: Ref<StoredArtidocSection[]>;
    let stored_section0: StoredArtidocSection;
    let stored_section1: StoredArtidocSection;
    let stored_section2: StoredArtidocSection;
    let stored_section3: StoredArtidocSection;
    let reorder: MockInstance;

    beforeEach(() => {
        stored_section0 = injectInternalId(FreetextSectionFactory.override({ display_title: "A" }));
        stored_section1 = injectInternalId(
            PendingArtifactSectionFactory.override({ display_title: "B" }),
        );
        stored_section2 = injectInternalId(ArtifactSectionFactory.override({ display_title: "C" }));
        stored_section3 = injectInternalId(ArtifactSectionFactory.override({ display_title: "D" }));

        reorder = vi.spyOn(rest, "reorderSections").mockReturnValue(okAsync({} as Response));
        sections = ref([stored_section0, stored_section1, stored_section2, stored_section3]);
        reorderer = buildSectionsReorderer(sections);
    });

    describe("moveSectionUp", () => {
        it("should do nothing if the section is already at the top", async () => {
            await reorderer.moveSectionUp(101, stored_section0);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending artifact section up", async () => {
            await reorderer.moveSectionUp(101, stored_section1);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "B",
                "A",
                "C",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section up", async () => {
            await reorderer.moveSectionUp(101, stored_section2);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "C",
                "B",
                "D",
            ]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.id,
                "before",
                stored_section3.id,
            );
        });

        it("should move an artifact section up and call reorder if it is above an artifact section", async () => {
            await reorderer.moveSectionUp(101, stored_section2);
            await reorderer.moveSectionUp(101, stored_section2);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "C",
                "A",
                "B",
                "D",
            ]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.id,
                "before",
                stored_section0.id,
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
            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
        });
    });

    describe("moveSectionDown", () => {
        it("should do nothing if the section is already at the bottom", async () => {
            await reorderer.moveSectionDown(101, stored_section3);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending artifact section down", async () => {
            await reorderer.moveSectionDown(101, stored_section1);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "C",
                "B",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section down", async () => {
            await reorderer.moveSectionDown(101, stored_section0);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "B",
                "A",
                "C",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section down and call reorder if it is below an artifact section", async () => {
            await reorderer.moveSectionDown(101, stored_section0);
            await reorderer.moveSectionDown(101, stored_section0);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "B",
                "C",
                "A",
                "D",
            ]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section0.id,
                "after",
                stored_section2.id,
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
            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
        });
    });

    describe("moveSectionBefore", () => {
        it("should do nothing if the section is moved at the same place", async () => {
            await reorderer.moveSectionBefore(101, stored_section1, stored_section2);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a section before a pending artifact section", async () => {
            await reorderer.moveSectionBefore(101, stored_section2, stored_section1);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "C",
                "B",
                "D",
            ]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.id,
                "before",
                stored_section3.id,
            );
        });

        it("should move a section before an artifact section", async () => {
            await reorderer.moveSectionBefore(101, stored_section2, stored_section0);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "C",
                "A",
                "B",
                "D",
            ]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section2.id,
                "before",
                stored_section0.id,
            );
        });

        it("A before C should move ABCD to BACD", async () => {
            await reorderer.moveSectionBefore(101, stored_section0, stored_section2);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "B",
                "A",
                "C",
                "D",
            ]);

            expect(reorder).toHaveBeenCalledOnce();
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section0.id,
                "before",
                stored_section2.id,
            );
        });

        it("When an error occurred, then it should not reorder the sections and return a Fault", async () => {
            const fault = Fault.fromMessage("Great Scott!");
            reorder.mockReturnValue(errAsync(fault));

            const result = await reorderer.moveSectionBefore(101, stored_section0, stored_section2);
            if (result.isOk()) {
                throw new Error("Expected an error");
            }

            expect(result.error).toBe(fault);
            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
        });
    });

    describe("moveSectionAtTheEnd", () => {
        it("should do nothing if the section is moved at the same place", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section3);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move a pending artifact section at the end", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section1);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "C",
                "D",
                "B",
            ]);
            expect(reorder).not.toHaveBeenCalled();
        });

        it("should move an artifact section at the end", async () => {
            await reorderer.moveSectionAtTheEnd(101, stored_section0);

            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "B",
                "C",
                "D",
                "A",
            ]);
            expect(reorder).toHaveBeenCalledWith(
                101,
                stored_section0.id,
                "after",
                stored_section3.id,
            );
        });

        it("When an error occurred, then it should not reorder the sections and return a Fault", async () => {
            const fault = Fault.fromMessage("Great Scott!");
            reorder.mockReturnValue(errAsync(fault));

            const result = await reorderer.moveSectionAtTheEnd(101, stored_section0);
            if (result.isOk()) {
                throw new Error("Expected an error");
            }

            expect(result.error).toBe(fault);
            expect(sections.value.map((section) => section.display_title)).toStrictEqual([
                "A",
                "B",
                "C",
                "D",
            ]);
        });
    });
});

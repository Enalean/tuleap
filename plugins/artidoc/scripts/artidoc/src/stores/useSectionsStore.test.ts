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

import { describe, it, vi, expect } from "vitest";
import { AT_THE_END, useSectionsStore } from "@/stores/useSectionsStore";
import * as rest from "@/helpers/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { Fault } from "@tuleap/fault";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";

describe("useSectionsStore", () => {
    describe("loadSections", () => {
        it("should have 3 dummy sections by default (for skeletons)", () => {
            const store = useSectionsStore();

            expect(store.sections.value).toHaveLength(3);
        });

        it("should store loaded sections", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([ArtifactSectionFactory.create()]),
            );

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            expect(store.sections.value).toHaveLength(1);
        });

        it("should store loaded sections when empty", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            expect(store.sections.value).toHaveLength(0);
        });

        it("should store undefined in case of error", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Oopsie!")),
            );

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            expect(store.sections.value).toBeUndefined();
        });
    });

    describe("is_sections_loading", () => {
        it("should says that sections are loading by default", () => {
            const store = useSectionsStore();

            expect(store.is_sections_loading.value).toBe(true);
        });

        it("should says that sections are not anymore loading when they are loaded #CaptainObvious", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([ArtifactSectionFactory.create()]),
            );

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            expect(store.is_sections_loading.value).toBe(false);
        });

        it("should says that sections are not anymore loading even in case of error", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Oopsie!")),
            );

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            expect(store.is_sections_loading.value).toBe(false);
        });
    });

    describe("updateSection", () => {
        it("should throw when we try to update a section while sections are undefined", async () => {
            const section = ArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Oopsie!")),
            );

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            expect(() => store.updateSection(section)).toThrow();
        });

        it("should update the section", async () => {
            const section = ArtifactSectionFactory.create();
            const section_a = ArtifactSectionFactory.override({
                ...section,
                id: "section-a",
                title: {
                    ...section.title,
                    value: "Section A",
                },
            });
            const section_b = ArtifactSectionFactory.override({
                ...section,
                id: "section-b",
                title: {
                    ...section.title,
                    value: "Section B",
                },
            });

            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section_a, section_b]));

            const store = useSectionsStore();
            store.loadSections(101);

            await flushPromises();

            store.updateSection(
                ArtifactSectionFactory.override({
                    ...section_b,
                    title: {
                        ...section_b.title,
                        value: "Updated section B",
                    },
                }),
            );

            expect(store.sections.value).toHaveLength(2);
            expect(store.sections.value?.[0].title.value).toBe("Section A");
            expect(store.sections.value?.[1].title.value).toBe("Updated section B");
        });
    });

    describe("removeSection", () => {
        it("should remove the section when it is found", () => {
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = ArtifactSectionFactory.create();
            const section4 = PendingArtifactSectionFactory.create();

            const store = useSectionsStore();
            store.sections.value = [section1, section2, section3, section4];

            store.removeSection(section2);
            store.removeSection(section3);

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(2);
            expect(store.sections.value[0]).toStrictEqual(section1);
            expect(store.sections.value[1]).toStrictEqual(section4);
        });

        it("should do nothing when there is no sections", () => {
            const store = useSectionsStore();
            store.sections.value = undefined;

            store.removeSection(ArtifactSectionFactory.create());

            expect(store.sections.value).toBeUndefined();
        });

        it("should do nothing when there is no sections", () => {
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = ArtifactSectionFactory.create();
            const section4 = PendingArtifactSectionFactory.create();

            const store = useSectionsStore();
            store.sections.value = [section1, section2, section3, section4];

            store.removeSection(ArtifactSectionFactory.create());

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(4);
            expect(store.sections.value[0]).toStrictEqual(section1);
            expect(store.sections.value[1]).toStrictEqual(section2);
            expect(store.sections.value[2]).toStrictEqual(section3);
            expect(store.sections.value[3]).toStrictEqual(section4);
        });
    });

    describe("insertSection", () => {
        const section1 = ArtifactSectionFactory.create();
        const section2 = PendingArtifactSectionFactory.create();
        const new_section = PendingArtifactSectionFactory.create();

        it("should do nothing when sections are undefined", () => {
            const store = useSectionsStore();
            store.sections.value = undefined;

            store.insertSection(PendingArtifactSectionFactory.create(), AT_THE_END);

            expect(store.sections.value).toBeUndefined();
        });

        it("should insert the section at the beginning", () => {
            const store = useSectionsStore();
            store.sections.value = [section1, section2];

            store.insertSection(new_section, { index: 0 });

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value[0]).toStrictEqual(new_section);
            expect(store.sections.value[1]).toStrictEqual(section1);
            expect(store.sections.value[2]).toStrictEqual(section2);
        });

        it("should insert the section before the second one", () => {
            const store = useSectionsStore();
            store.sections.value = [section1, section2];

            store.insertSection(new_section, { index: 1 });

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value[0]).toStrictEqual(section1);
            expect(store.sections.value[1]).toStrictEqual(new_section);
            expect(store.sections.value[2]).toStrictEqual(section2);
        });

        it("should insert the section at the end", () => {
            const store = useSectionsStore();
            store.sections.value = [section1, section2];

            store.insertSection(new_section, AT_THE_END);

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value[0]).toStrictEqual(section1);
            expect(store.sections.value[1]).toStrictEqual(section2);
            expect(store.sections.value[2]).toStrictEqual(new_section);
        });
    });
});

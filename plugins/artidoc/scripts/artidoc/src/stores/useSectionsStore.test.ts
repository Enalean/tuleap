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
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import type { Tracker } from "@/stores/configuration-store";
import { isPendingArtifactSection } from "@/helpers/artidoc-section.type";
import type { Project } from "@/helpers/project.type";

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
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, false, { id: 101 } as Project);

            await flushPromises();

            expect(store.sections.value).toHaveLength(1);
        });

        it("should create an internal id because when section are replaced (pending section -> artifact section) the fake id is replaced by the real one and it could mess up the v-for.key", async () => {
            const section = ArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, false, { id: 101 } as Project);

            await flushPromises();

            expect(store.sections.value?.[0]?.internal_id).toBeDefined();
            expect(store.sections.value?.[0]?.id).toBe(section.id);
            expect(store.sections.value?.[0]?.internal_id).not.toBe(section.id);
        });

        it.each([
            [null],
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
        ])(
            "should store loaded sections when empty and user can edit document and configured tracker = %s",
            async (tracker: Tracker | null) => {
                vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));

                const store = useSectionsStore();
                store.loadSections(101, tracker, true, { id: 101 } as Project);

                await flushPromises();

                expect(store.sections.value).toHaveLength(0);
            },
        );

        it("should store loaded sections when empty and configured tracker but no rights to edit document", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, TrackerStub.withTitleAndDescription(), false, {
                id: 101,
            } as Project);

            await flushPromises();

            expect(store.sections.value).toHaveLength(0);
        });

        it(`should create a pending artifact section
            when loaded sections are empty
            and there is a configured tracker
            and user can edit document`, async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, TrackerStub.withTitleAndDescription(), true, {
                id: 101,
            } as Project);

            await flushPromises();

            expect(store.sections.value).toHaveLength(1);
        });

        it("should store undefined in case of error", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Oopsie!")),
            );

            const store = useSectionsStore();
            store.loadSections(101, null, false, { id: 101 } as Project);

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
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            await store.loadSections(101, null, false, { id: 101 } as Project);

            await flushPromises();

            expect(store.is_sections_loading.value).toBe(false);
        });

        it("should says that sections are not anymore loading even in case of error", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Oopsie!")),
            );

            const store = useSectionsStore();
            store.loadSections(101, null, false, { id: 101 } as Project);

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
            store.loadSections(101, null, true, { id: 101 } as Project);

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
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);

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
        it.each([
            [null],
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
            [TrackerStub.withTitleAndDescription()],
        ])(
            "should remove the section when it is found and tracker is %s",
            async (tracker: Tracker | null) => {
                const section1 = ArtifactSectionFactory.create();
                const section2 = PendingArtifactSectionFactory.create();
                const section3 = ArtifactSectionFactory.create();
                const section4 = PendingArtifactSectionFactory.create();

                vi.spyOn(rest, "getAllSections").mockReturnValue(
                    okAsync([section1, section2, section3, section4]),
                );
                vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));
                vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

                const store = useSectionsStore();
                store.loadSections(101, null, true, { id: 101 } as Project);
                await flushPromises();

                store.removeSection(section2, tracker);
                store.removeSection(section3, null);
                await flushPromises();

                expect(store.sections.value).not.toBeUndefined();
                expect(store.sections.value).toHaveLength(2);
                expect(store.sections.value?.[0].id).toStrictEqual(section1.id);
                expect(store.sections.value?.[1].id).toStrictEqual(section4.id);
            },
        );

        it.each([
            [null],
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
        ])(
            "should remove the last section and end up with empty sections when tracker is %s",
            async (tracker: Tracker | null) => {
                const section = ArtifactSectionFactory.create();

                vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section]));
                vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));
                vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

                const store = useSectionsStore();
                store.loadSections(101, null, true, { id: 101 } as Project);
                await flushPromises();

                store.removeSection(section, tracker);
                await flushPromises();

                expect(store.sections.value).not.toBeUndefined();
                expect(store.sections.value).toHaveLength(0);
            },
        );

        it("should remove the last section and add automatically a fresh new one when tracker has title and description", async () => {
            const section = ArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));
            vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.removeSection(section, TrackerStub.withTitleAndDescription());
            await flushPromises();

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(1);
            const pending = store.sections.value?.[0];
            if (pending === undefined) {
                throw Error("Should get a section");
            }
            expect(isPendingArtifactSection(pending)).toBe(true);
        });

        it("should do nothing when there is no sections", async () => {
            const store = useSectionsStore();
            store.sections.value = undefined;

            store.removeSection(ArtifactSectionFactory.create(), null);
            await flushPromises();

            expect(store.sections.value).toBeUndefined();
        });

        it("should do nothing when section cannot be found", async () => {
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = ArtifactSectionFactory.create();
            const section4 = PendingArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([section1, section2, section3, section4]),
            );
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.removeSection(ArtifactSectionFactory.create(), null);
            await flushPromises();

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(4);
            expect(store.sections.value?.[0].id).toStrictEqual(section1.id);
            expect(store.sections.value?.[1].id).toStrictEqual(section2.id);
            expect(store.sections.value?.[2].id).toStrictEqual(section3.id);
            expect(store.sections.value?.[3].id).toStrictEqual(section4.id);
        });
    });

    describe("insertSection", () => {
        const section1 = ArtifactSectionFactory.create();
        const section2 = PendingArtifactSectionFactory.create();
        const new_section = PendingArtifactSectionFactory.create();

        it("should do nothing when sections are undefined", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertSection(PendingArtifactSectionFactory.create(), AT_THE_END);

            expect(store.sections.value).toBeUndefined();
        });

        it("should insert the section at the beginning", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section1, section2]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertSection(new_section, { before: section1.id });

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value?.[0].id).toStrictEqual(new_section.id);
            expect(store.sections.value?.[1].id).toStrictEqual(section1.id);
            expect(store.sections.value?.[2].id).toStrictEqual(section2.id);
        });

        it("should insert the section before the second one", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section1, section2]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertSection(new_section, { before: section2.id });

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value?.[0].id).toStrictEqual(section1.id);
            expect(store.sections.value?.[1].id).toStrictEqual(new_section.id);
            expect(store.sections.value?.[2].id).toStrictEqual(section2.id);
        });

        it("should insert the section at the end", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section1, section2]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertSection(new_section, AT_THE_END);

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value?.[0].id).toStrictEqual(section1.id);
            expect(store.sections.value?.[1].id).toStrictEqual(section2.id);
            expect(store.sections.value?.[2].id).toStrictEqual(new_section.id);
        });
    });

    describe("insertPendingArtifactSectionForEmptyDocument", () => {
        it.each([
            [null],
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
        ])("should do nothing if tracker is %s", async (tracker: Tracker | null) => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertPendingArtifactSectionForEmptyDocument(tracker);

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(0);
        });

        it("should insert a pending artifact section when sections is empty", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertPendingArtifactSectionForEmptyDocument(
                TrackerStub.withTitleAndDescription(),
            );

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(1);
            const section = store.sections.value?.[0];
            if (section === undefined) {
                throw Error("Should get a section");
            }
            expect(isPendingArtifactSection(section));
        });

        it("should do nothing when loading of sections failed", () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            const store = useSectionsStore();
            store.sections.value = undefined;

            store.insertPendingArtifactSectionForEmptyDocument(
                TrackerStub.withTitleAndDescription(),
            );

            expect(store.sections.value).toBeUndefined();
        });

        it("should do nothing when not empty", async () => {
            const section = ArtifactSectionFactory.create();
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.insertPendingArtifactSectionForEmptyDocument(
                TrackerStub.withTitleAndDescription(),
            );

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(1);
            expect(store.sections.value?.[0].id).toStrictEqual(section.id);
        });
    });

    describe("getSectionPositionForSave", () => {
        describe("scenario that should not happen  (how can we have a section to get position, but no sections at all in the store?)", () => {
            it("should return at the end if section is not found", async () => {
                vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));

                const store = useSectionsStore();
                store.loadSections(101, null, true, { id: 101 } as Project);
                await flushPromises();

                expect(
                    store.getSectionPositionForSave(PendingArtifactSectionFactory.create()),
                ).toBeNull();
            });

            it("should return at the end if loading of sections failed", async () => {
                vi.spyOn(rest, "getAllSections").mockReturnValue(
                    errAsync(Fault.fromMessage("Bad request")),
                );

                const store = useSectionsStore();
                store.loadSections(101, null, true, { id: 101 } as Project);
                await flushPromises();

                expect(
                    store.getSectionPositionForSave(PendingArtifactSectionFactory.create()),
                ).toBeNull();
            });
        });

        it("should return the position that could be used for save", async () => {
            const section0 = ArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = ArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([section0, section1, section2]),
            );
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            expect(store.getSectionPositionForSave(section0)).toStrictEqual({
                before: section1.id,
            });
            expect(store.getSectionPositionForSave(section1)).toStrictEqual({
                before: section2.id,
            });
            expect(store.getSectionPositionForSave(section2)).toBeNull();
        });

        it("should return the position by excluding pending artifact section because we cannot position a section with a non-existing-yet section", async () => {
            const section0 = PendingArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = PendingArtifactSectionFactory.create();
            const section4 = ArtifactSectionFactory.create();
            const section5 = PendingArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([section0, section1, section2, section3, section4, section5]),
            );
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            expect(store.getSectionPositionForSave(section0)).toStrictEqual({
                before: section1.id,
            });
            expect(store.getSectionPositionForSave(section1)).toStrictEqual({
                before: section4.id,
            });
            expect(store.getSectionPositionForSave(section2)).toStrictEqual({
                before: section4.id,
            });
            expect(store.getSectionPositionForSave(section3)).toStrictEqual({
                before: section4.id,
            });
            expect(store.getSectionPositionForSave(section4)).toBeNull();
            expect(store.getSectionPositionForSave(section5)).toBeNull();
        });
    });

    describe("replacePendingByArtifactSection", () => {
        it("should do nothing if loading of sections failed", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.replacePendingByArtifactSection(
                PendingArtifactSectionFactory.create(),
                ArtifactSectionFactory.create(),
            );

            expect(store.sections.value).toBe(undefined);
        });

        it("should do nothing if sections is empty", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.replacePendingByArtifactSection(
                PendingArtifactSectionFactory.create(),
                ArtifactSectionFactory.create(),
            );

            expect(store.sections.value).toStrictEqual([]);
        });

        it("should do nothing if the pending sections cannot be found", async () => {
            const section = PendingArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(okAsync([section]));
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            store.replacePendingByArtifactSection(
                PendingArtifactSectionFactory.create(),
                ArtifactSectionFactory.create(),
            );

            expect(store.sections.value).toHaveLength(1);
            expect(store.sections.value?.[0].id).toStrictEqual(section.id);
        });

        it("should replace the section", async () => {
            const section0 = PendingArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = PendingArtifactSectionFactory.create();

            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([section0, section1, section2, section3]),
            );
            vi.spyOn(rest, "getReferences").mockReturnValue(okAsync([]));

            const store = useSectionsStore();
            store.loadSections(101, null, true, { id: 101 } as Project);
            await flushPromises();

            const newone = ArtifactSectionFactory.create();

            store.replacePendingByArtifactSection(section2, newone);

            expect(store.sections.value).not.toBeUndefined();
            expect(store.sections.value).toHaveLength(4);
            expect(store.sections.value?.[0].id).toStrictEqual(section0.id);
            expect(store.sections.value?.[1].id).toStrictEqual(section1.id);
            expect(store.sections.value?.[2].id).toStrictEqual(newone.id);
            expect(store.sections.value?.[3].id).toStrictEqual(section3.id);
        });
    });
});

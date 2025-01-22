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

import { describe, it, expect } from "vitest";
import type { SectionsStore } from "@/stores/useSectionsStore";
import { buildSectionsStore } from "@/stores/useSectionsStore";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { CreateStoredSections } from "@/stores/CreateStoredSections";

describe("buildSectionsStore", () => {
    const getEmptyStore = (): SectionsStore => buildSectionsStore();

    const getStoreWithSections = (sections: ArtidocSection[]): SectionsStore => {
        const store = buildSectionsStore();
        store.replaceAll(CreateStoredSections.fromArtidocSectionsCollection(sections));
        return store;
    };

    it("should have no sections by default", () => {
        const store = getEmptyStore();

        expect(store.sections.value).toHaveLength(0);
    });

    describe("replaceAll", () => {
        it("should store loaded sections", () => {
            const store = getEmptyStore();

            store.replaceAll(
                CreateStoredSections.fromArtidocSectionsCollection([
                    ArtifactSectionFactory.create(),
                    FreetextSectionFactory.create(),
                ]),
            );

            expect(store.sections.value).toHaveLength(2);
        });

        it("should create an internal id because when section are replaced (pending section -> artifact section) the fake id is replaced by the real one and it could mess up the v-for.key", () => {
            const store = getEmptyStore();
            const section = ArtifactSectionFactory.create();

            store.replaceAll([CreateStoredSections.fromArtidocSection(section)]);

            expect(store.sections.value[0]?.internal_id).toBeDefined();
            expect(store.sections.value[0]?.id).toBe(section.id);
            expect(store.sections.value[0]?.internal_id).not.toBe(section.id);
        });
    });

    describe("getSectionPositionForSave", () => {
        describe("scenario that should not happen (how can we have a section to get position, but no sections at all in the store?)", () => {
            it("should return at the end if section is not found", () => {
                const store = getEmptyStore();

                expect(
                    store.getSectionPositionForSave(PendingArtifactSectionFactory.create()),
                ).toBeNull();
            });
        });

        it("should return the position that could be used for save", () => {
            const section0 = ArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = ArtifactSectionFactory.create();
            const store = getStoreWithSections([section0, section1, section2]);

            expect(store.getSectionPositionForSave(section0)).toStrictEqual({
                before: section1.id,
            });
            expect(store.getSectionPositionForSave(section1)).toStrictEqual({
                before: section2.id,
            });
            expect(store.getSectionPositionForSave(section2)).toBeNull();
        });

        it("should return the position by excluding pending artifact section because we cannot position a section with a non-existing-yet section", () => {
            const section0 = PendingArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = PendingArtifactSectionFactory.create();
            const section4 = ArtifactSectionFactory.create();
            const section5 = PendingArtifactSectionFactory.create();

            const store = getStoreWithSections([
                section0,
                section1,
                section2,
                section3,
                section4,
                section5,
            ]);

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
});

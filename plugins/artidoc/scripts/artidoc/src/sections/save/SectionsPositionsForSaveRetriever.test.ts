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

import { describe, it, expect } from "vitest";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { getSectionsPositionsForSaveRetriever } from "@/sections/save/SectionsPositionsForSaveRetriever";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

describe("SectionsPositionsForSaveRetriever", () => {
    const getEmptySectionsCollection = (): SectionsCollection =>
        buildSectionsCollection(SectionsStatesCollectionStub.build());

    const getCollectionWithSections = (sections: ArtidocSection[]): SectionsCollection => {
        const collection = getEmptySectionsCollection();
        collection.replaceAll(ReactiveStoredArtidocSectionStub.fromCollection(sections));
        return collection;
    };

    describe("scenario that should not happen (how can we have a section to get position, but no sections at all in the collection?)", () => {
        it("should return at the end if section is not found", () => {
            const retriever = getSectionsPositionsForSaveRetriever(getEmptySectionsCollection());

            expect(
                retriever.getSectionPositionForSave(PendingArtifactSectionFactory.create()),
            ).toBeNull();
        });
    });

    it("should return the position that could be used for save", () => {
        const section0 = ArtifactSectionFactory.create();
        const section1 = ArtifactSectionFactory.create();
        const section2 = ArtifactSectionFactory.create();
        const retriever = getSectionsPositionsForSaveRetriever(
            getCollectionWithSections([section0, section1, section2]),
        );

        expect(retriever.getSectionPositionForSave(section0)).toStrictEqual({
            before: section1.id,
        });
        expect(retriever.getSectionPositionForSave(section1)).toStrictEqual({
            before: section2.id,
        });
        expect(retriever.getSectionPositionForSave(section2)).toBeNull();
    });

    it("should return the position by excluding pending artifact section because we cannot position a section with a non-existing-yet section", () => {
        const section0 = PendingArtifactSectionFactory.create();
        const section1 = ArtifactSectionFactory.create();
        const section2 = PendingArtifactSectionFactory.create();
        const section3 = PendingArtifactSectionFactory.create();
        const section4 = ArtifactSectionFactory.create();
        const section5 = PendingArtifactSectionFactory.create();

        const retriever = getSectionsPositionsForSaveRetriever(
            getCollectionWithSections([section0, section1, section2, section3, section4, section5]),
        );

        expect(retriever.getSectionPositionForSave(section0)).toStrictEqual({
            before: section1.id,
        });
        expect(retriever.getSectionPositionForSave(section1)).toStrictEqual({
            before: section4.id,
        });
        expect(retriever.getSectionPositionForSave(section2)).toStrictEqual({
            before: section4.id,
        });
        expect(retriever.getSectionPositionForSave(section3)).toStrictEqual({
            before: section4.id,
        });
        expect(retriever.getSectionPositionForSave(section4)).toBeNull();
        expect(retriever.getSectionPositionForSave(section5)).toBeNull();
    });
});

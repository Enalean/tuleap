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

import { describe, it, expect, beforeEach } from "vitest";
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { AT_THE_END, getSectionsInserter } from "@/sections/insert/SectionsInserter";
import type { InsertSections } from "@/sections/insert/SectionsInserter";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

const section1 = ArtifactSectionFactory.create();
const section2 = PendingArtifactSectionFactory.create();
const new_section = PendingArtifactSectionFactory.create();

describe("SectionsInserter", () => {
    let sections_collection: SectionsCollection,
        states_collection: SectionsStatesCollection,
        inserter: InsertSections;

    beforeEach(() => {
        states_collection = SectionsStatesCollectionStub.build();
        sections_collection = buildSectionsCollection(states_collection);
        sections_collection.replaceAll(
            ReactiveStoredArtidocSectionStub.fromCollection([section1, section2]),
        );

        inserter = getSectionsInserter(sections_collection, states_collection);
    });

    const expectSectionStateToHaveBeenCreated = (section_index: number): void => {
        const section = sections_collection.sections.value[section_index].value;
        expect(states_collection.getSectionState(section)).toBeDefined();
    };

    it("should insert the section at the beginning", () => {
        inserter.insertSection(new_section, { before: section1.id });

        expect(sections_collection.sections.value).toHaveLength(3);
        expect(sections_collection.sections.value[0].value.id).toStrictEqual(new_section.id);
        expect(sections_collection.sections.value[1].value.id).toStrictEqual(section1.id);
        expect(sections_collection.sections.value[2].value.id).toStrictEqual(section2.id);

        expectSectionStateToHaveBeenCreated(0);
        expectSectionStateToHaveBeenCreated(1);
        expectSectionStateToHaveBeenCreated(2);
    });

    it("should insert the section before the second one", () => {
        inserter.insertSection(new_section, { before: section2.id });

        expect(sections_collection.sections.value).toHaveLength(3);
        expect(sections_collection.sections.value[0].value.id).toStrictEqual(section1.id);
        expect(sections_collection.sections.value[1].value.id).toStrictEqual(new_section.id);
        expect(sections_collection.sections.value[2].value.id).toStrictEqual(section2.id);

        expectSectionStateToHaveBeenCreated(0);
        expectSectionStateToHaveBeenCreated(1);
        expectSectionStateToHaveBeenCreated(2);
    });

    it("should insert the section at the end", () => {
        inserter.insertSection(new_section, AT_THE_END);

        expect(sections_collection.sections.value).toHaveLength(3);
        expect(sections_collection.sections.value[0].value.id).toStrictEqual(section1.id);
        expect(sections_collection.sections.value[1].value.id).toStrictEqual(section2.id);
        expect(sections_collection.sections.value[2].value.id).toStrictEqual(new_section.id);

        expectSectionStateToHaveBeenCreated(0);
        expectSectionStateToHaveBeenCreated(1);
        expectSectionStateToHaveBeenCreated(2);
    });
});

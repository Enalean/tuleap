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
import { CreateStoredSections } from "@/sections/CreateStoredSections";
import { AT_THE_END, getSectionsInserter } from "@/sections/SectionsInserter";
import type { InsertSections } from "@/sections/SectionsInserter";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";

const section1 = ArtifactSectionFactory.create();
const section2 = PendingArtifactSectionFactory.create();
const new_section = PendingArtifactSectionFactory.create();

describe("SectionsInserter", () => {
    let sections_collection: SectionsCollection, inserter: InsertSections;

    beforeEach(() => {
        sections_collection = buildSectionsCollection();
        sections_collection.replaceAll(
            CreateStoredSections.fromArtidocSectionsCollection([section1, section2]),
        );

        inserter = getSectionsInserter(sections_collection);
    });

    it("should insert the section at the beginning", () => {
        inserter.insertSection(new_section, { before: section1.id });

        expect(sections_collection.sections.value).toHaveLength(3);
        expect(sections_collection.sections.value[0].id).toStrictEqual(new_section.id);
        expect(sections_collection.sections.value[1].id).toStrictEqual(section1.id);
        expect(sections_collection.sections.value[2].id).toStrictEqual(section2.id);
    });

    it("should insert the section before the second one", () => {
        inserter.insertSection(new_section, { before: section2.id });

        expect(sections_collection.sections.value).toHaveLength(3);
        expect(sections_collection.sections.value[0].id).toStrictEqual(section1.id);
        expect(sections_collection.sections.value[1].id).toStrictEqual(new_section.id);
        expect(sections_collection.sections.value[2].id).toStrictEqual(section2.id);
    });

    it("should insert the section at the end", () => {
        inserter.insertSection(new_section, AT_THE_END);

        expect(sections_collection.sections.value).toHaveLength(3);
        expect(sections_collection.sections.value[0].id).toStrictEqual(section1.id);
        expect(sections_collection.sections.value[1].id).toStrictEqual(section2.id);
        expect(sections_collection.sections.value[2].id).toStrictEqual(new_section.id);
    });
});

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
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { isPendingFreetextSection } from "@/helpers/artidoc-section.type";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { getPendingSectionsReplacer } from "@/sections/insert/PendingSectionsReplacer";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

describe("PendingSectionsReplacer", () => {
    const getSectionsCollection = (sections: ArtidocSection[]): SectionsCollection => {
        const collection = buildSectionsCollection(SectionsStatesCollectionStub.build());
        collection.replaceAll(ReactiveStoredArtidocSectionStub.fromCollection(sections));

        return collection;
    };

    it("should do nothing if the pending sections cannot be found", () => {
        const section = PendingArtifactSectionFactory.create();
        const sections = getSectionsCollection([section]);
        const replacer = getPendingSectionsReplacer(sections, SectionsStatesCollectionStub.build());

        replacer.replacePendingSection(
            PendingArtifactSectionFactory.create(),
            ArtifactSectionFactory.create(),
        );

        const replacement_section = sections.sections.value[0];

        expect(sections.sections.value).toHaveLength(1);
        expect(replacement_section.value.id).toStrictEqual(section.id);
    });

    it("should replace an artifact section", () => {
        const section0 = PendingArtifactSectionFactory.create();
        const section1 = ArtifactSectionFactory.create();
        const section2 = PendingArtifactSectionFactory.create();
        const section3 = PendingArtifactSectionFactory.create();
        const newone = ArtifactSectionFactory.create();

        const sections = getSectionsCollection([section0, section1, section2, section3]);
        const replacer = getPendingSectionsReplacer(sections, SectionsStatesCollectionStub.build());

        replacer.replacePendingSection(section2, newone);

        expect(sections.sections.value).toHaveLength(4);
        expect(sections.sections.value[0].value.id).toStrictEqual(section0.id);
        expect(sections.sections.value[1].value.id).toStrictEqual(section1.id);
        expect(sections.sections.value[2].value.id).toStrictEqual(newone.id);
        expect(sections.sections.value[3].value.id).toStrictEqual(section3.id);
    });

    it("should replace a freetext section", () => {
        const section0 = FreetextSectionFactory.create();
        const section1 = FreetextSectionFactory.pending();
        const section2 = FreetextSectionFactory.create();
        const section3 = FreetextSectionFactory.create();
        const newone = FreetextSectionFactory.create();

        const sections = getSectionsCollection([section0, section1, section2, section3]);
        const replacer = getPendingSectionsReplacer(sections, SectionsStatesCollectionStub.build());

        if (!isPendingFreetextSection(section1)) {
            throw new Error("Expected a pending freetext section");
        }

        replacer.replacePendingSection(section1, newone);

        expect(sections.sections.value).toHaveLength(4);
        expect(sections.sections.value[0].value.id).toStrictEqual(section0.id);
        expect(sections.sections.value[1].value.id).toStrictEqual(newone.id);
        expect(sections.sections.value[2].value.id).toStrictEqual(section2.id);
        expect(sections.sections.value[3].value.id).toStrictEqual(section3.id);
    });
});

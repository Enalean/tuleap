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
import { buildSectionsCollection } from "@/stores/SectionsCollection";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { CreateStoredSections } from "@/stores/CreateStoredSections";

describe("SectionsCollection", () => {
    it("should have no sections by default", () => {
        const collection = buildSectionsCollection();

        expect(collection.sections.value).toHaveLength(0);
    });

    describe("replaceAll", () => {
        it("should store loaded sections", () => {
            const collection = buildSectionsCollection();

            collection.replaceAll(
                CreateStoredSections.fromArtidocSectionsCollection([
                    ArtifactSectionFactory.create(),
                    FreetextSectionFactory.create(),
                ]),
            );

            expect(collection.sections.value).toHaveLength(2);
        });

        it("should create an internal id because when section are replaced (pending section -> artifact section) the fake id is replaced by the real one and it could mess up the v-for.key", () => {
            const collection = buildSectionsCollection();
            const section = ArtifactSectionFactory.create();

            collection.replaceAll([CreateStoredSections.fromArtidocSection(section)]);

            expect(collection.sections.value[0]?.internal_id).toBeDefined();
            expect(collection.sections.value[0]?.id).toBe(section.id);
            expect(collection.sections.value[0]?.internal_id).not.toBe(section.id);
        });
    });
});

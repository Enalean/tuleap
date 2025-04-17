/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { buildSectionsBelowArtifactsDetector } from "@/sections/levels/SectionsBelowArtifactsDetector";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

describe(`ArtifactsWithSubsectionsDetector`, () => {
    function* generateStructureCases(): Generator<[ReactiveStoredArtidocSection[], string[]]> {
        const top_level_artifact = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ level: 1 }),
        );
        const level_two_artifact = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ level: 2 }),
        );
        const other_level_two_artifact = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ level: 2 }),
        );
        const level_three_artifact = ReactiveStoredArtidocSectionStub.fromSection(
            ArtifactSectionFactory.override({ level: 3 }),
        );

        const top_level_freetext = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ level: 1 }),
        );
        const other_top_level_freetext = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ level: 1 }),
        );
        const level_two_freetext = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ level: 2 }),
        );
        const level_three_freetext = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ level: 3 }),
        );
        const other_level_three_freetext = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({ level: 3 }),
        );

        // Empty is allowed
        yield [[], []];

        // Valid structures
        yield [[top_level_freetext], []];
        yield [[top_level_artifact, top_level_freetext], []];
        // Gaps in the levels are ok
        yield [
            [
                top_level_freetext,
                level_two_freetext,
                level_three_freetext,
                other_top_level_freetext,
                other_level_three_freetext,
            ],
            [],
        ];
        // Structures with only level 2 or level 3 are ok
        yield [[level_two_freetext, level_two_artifact], []];
        yield [[level_three_freetext, level_three_artifact], []];
        // Artifact without levels below them are ok
        yield [
            [
                top_level_artifact,
                top_level_freetext,
                level_two_artifact,
                other_top_level_freetext,
                level_three_artifact,
            ],
            [],
        ];

        // Free-text cannot be below artifact
        yield [[top_level_artifact, level_two_freetext], [level_two_freetext.value.internal_id]];
        yield [
            [top_level_artifact, level_three_freetext],
            [level_three_freetext.value.internal_id],
        ];
        yield [
            [level_two_artifact, level_three_freetext],
            [level_three_freetext.value.internal_id],
        ];
        // Artifact cannot be below artifact
        yield [[top_level_artifact, level_two_artifact], [level_two_artifact.value.internal_id]];
        yield [
            [top_level_artifact, level_three_artifact],
            [level_three_artifact.value.internal_id],
        ];
        yield [
            [level_two_artifact, level_three_artifact],
            [level_three_artifact.value.internal_id],
        ];
        // When several issues are found for the same parent, each section is marked
        yield [
            [top_level_artifact, level_two_freetext, level_two_artifact],
            [level_two_freetext.value.internal_id, level_two_artifact.value.internal_id],
        ];
        yield [
            [level_two_artifact, level_three_artifact, level_three_freetext],
            [level_three_artifact.value.internal_id, level_three_freetext.value.internal_id],
        ];
        // When several issues are found throughout the document, each section is marked
        yield [
            [
                top_level_freetext,
                level_two_artifact,
                level_three_freetext,
                top_level_artifact,
                other_top_level_freetext,
                other_level_two_artifact,
                other_level_three_freetext,
            ],
            [level_three_freetext.value.internal_id, other_level_three_freetext.value.internal_id],
        ];
    }

    it.each([...generateStructureCases()])(
        `will return an array of internal IDs for each section it finds below an artifact section`,
        (sections, expected_bad_section_ids) => {
            const sections_below_artifact = buildSectionsBelowArtifactsDetector().detect(sections);
            expect(sections_below_artifact).toHaveLength(expected_bad_section_ids.length);
            expect(sections_below_artifact).toStrictEqual(expected_bad_section_ids);
        },
    );
});

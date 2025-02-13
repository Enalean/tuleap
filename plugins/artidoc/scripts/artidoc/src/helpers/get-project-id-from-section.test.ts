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
import type { ArtifactSection } from "@/helpers/artidoc-section.type";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { getProjectIdFromSection } from "@/helpers/get-project-id-from-section";

const project_id = 105;

describe("get-project-id-from-section", () => {
    it("should return the project id of an artifact section", () => {
        const id = getProjectIdFromSection(
            CreateStoredSections.fromArtidocSection(
                ArtifactSectionFactory.override({
                    artifact: {
                        tracker: {
                            project: {
                                id: project_id,
                            },
                        },
                    },
                } as ArtifactSection),
            ),
        );

        expect(id).toBe(project_id);
    });

    it("should return the project id of a pending artifact section", () => {
        const id = getProjectIdFromSection(
            CreateStoredSections.fromArtidocSection(
                PendingArtifactSectionFactory.override({
                    tracker: TrackerStub.withProjectId(project_id),
                }),
            ),
        );

        expect(id).toBe(project_id);
    });

    it("should return null when the section is a freetext section", () => {
        const freetext_section = CreateStoredSections.fromArtidocSection(
            FreetextSectionFactory.create(),
        );
        const pending_freetext_section = CreateStoredSections.fromArtidocSection(
            FreetextSectionFactory.pending(),
        );

        expect(getProjectIdFromSection(freetext_section)).toBeNull();
        expect(getProjectIdFromSection(pending_freetext_section)).toBeNull();
    });
});

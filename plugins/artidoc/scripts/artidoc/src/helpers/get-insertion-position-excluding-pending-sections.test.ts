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

import { describe, expect, it } from "vitest";
import { getInsertionPositionExcludingPendingSections } from "@/helpers/get-insertion-position-excluding-pending-sections";
import { AT_THE_END } from "@/stores/useSectionsStore";
import { injectInternalId } from "@/helpers/inject-internal-id";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";

describe("get-insertion-position-excluding-pending-sections", () => {
    it("should return at the end, if section is asked to be put at the end", () => {
        const sections = [injectInternalId(ArtifactSectionFactory.create())];

        expect(getInsertionPositionExcludingPendingSections(AT_THE_END, sections)).toBe(AT_THE_END);
    });

    it("should return at the end, if sibling is not found", () => {
        const sections = [injectInternalId(ArtifactSectionFactory.create())];

        const unknown = ArtifactSectionFactory.create();

        expect(getInsertionPositionExcludingPendingSections({ before: unknown.id }, sections)).toBe(
            AT_THE_END,
        );
    });

    it("should skip pending artifact section, so that backend can insert the section next to an already saved section", () => {
        const pending1 = PendingArtifactSectionFactory.create();
        const pending2 = PendingArtifactSectionFactory.create();
        const pending3 = PendingArtifactSectionFactory.create();
        const artifact_section = ArtifactSectionFactory.create();

        const sections = [
            injectInternalId(pending1),
            injectInternalId(pending2),
            injectInternalId(artifact_section),
            injectInternalId(pending3),
        ];

        expect(
            getInsertionPositionExcludingPendingSections({ before: pending1.id }, sections),
        ).toStrictEqual({ before: artifact_section.id });
    });

    it("should return at the end, if there is only pending artifact sections", () => {
        const pending1 = PendingArtifactSectionFactory.create();
        const pending2 = PendingArtifactSectionFactory.create();
        const pending3 = PendingArtifactSectionFactory.create();

        const sections = [
            injectInternalId(pending1),
            injectInternalId(pending2),
            injectInternalId(pending3),
        ];

        expect(
            getInsertionPositionExcludingPendingSections({ before: pending1.id }, sections),
        ).toStrictEqual(AT_THE_END);
    });
});
